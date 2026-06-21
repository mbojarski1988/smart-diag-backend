# Design: Uwierzytelnianie użytkowników (admin / pracownik)

**Data:** 2026-06-21
**Status:** zatwierdzona

## Cel

Dodanie obsługi zalogowanych użytkowników (administratorów i pracowników) do istniejącego Symfony 7 JSON API, z JWT jako mechanizmem uwierzytelniania. Aplikacja Vue (oddzielne repo) komunikuje się z API przez REST.

## Role

| Rola | Uprawnienia |
|---|---|
| `ROLE_ADMIN` | Pełny dostęp: zarządzanie użytkownikami, licencjami, ustawieniami |
| `ROLE_EMPLOYEE` | Odczyt danych: dekodowanie VIN, przeglądanie licencji |

## Nowe pakiety

```
symfony/security-bundle
lexik/jwt-authentication-bundle
nelmio/cors-bundle
```

`nelmio/api-doc-bundle` przeniesiony z `require-dev` do `require`.

## Architektura

### Nowy bounded context: `src/User/`

```
src/User/
  Domain/
    User.php                  # encja Doctrine implementująca UserInterface
  Application/
    Dto/UserWriteRequest.php  # DTO dla POST/PATCH
    UserManager.php           # tworzenie, edycja, soft-delete
  Infrastructure/
    UserRepository.php
  Command/
    CreateAdminCommand.php    # php bin/console app:create-admin
```

### Rozszerzenie `src/Shared/Auth/`

```
src/Shared/Auth/
  Attribute/
    RequiresAuth.php          # nowy: wymaga ważnego JWT (dowolna rola)
    RequiresRole.php          # nowy: wymaga konkretnej roli (np. ROLE_ADMIN)
    RequiresAdmin.php         # istniejący — X-Admin-Key, bez zmian
    RequiresLicense.php       # istniejący — X-License-Key, bez zmian
  EventListener/
    AuthorizationListener.php # rozszerzony o obsługę RequiresRole
```

### Nowe kontrolery

```
src/Controller/Api/
  AuthController.php          # POST /api/auth/login, GET /api/auth/me
  UserAdminController.php     # CRUD pod /api/admin/users
```

## Baza danych — tabela `users`

| Kolumna | Typ | Uwagi |
|---|---|---|
| `id` | INT, PK | auto-increment |
| `email` | VARCHAR(255), UNIQUE | login |
| `password` | VARCHAR(255) | bcrypt hash |
| `first_name` | VARCHAR(100) | imię |
| `last_name` | VARCHAR(100) | nazwisko |
| `role` | VARCHAR(20) | `ROLE_ADMIN` / `ROLE_EMPLOYEE` |
| `active` | BOOL | domyślnie `true` |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | |
| `deleted_at` | DATETIME, nullable | soft-delete |

## Endpointy API

### Autentykacja

| Metoda | Ścieżka | Auth | Opis |
|---|---|---|---|
| `POST` | `/api/auth/login` | brak | Email + hasło → JWT (TTL 1h) |
| `GET` | `/api/auth/me` | JWT (`RequiresAuth`) | Dane zalogowanego użytkownika |

**Request login:**
```json
{ "email": "jan@example.com", "password": "tajneHaslo123" }
```

**Response login:**
```json
{
  "token": "eyJ...",
  "user": {
    "id": 1,
    "email": "jan@example.com",
    "firstName": "Jan",
    "lastName": "Kowalski",
    "role": "ROLE_ADMIN",
    "active": true
  }
}
```

**Response GET /api/auth/me** — ten sam obiekt `user` (bez pola `token`).

### Zarządzanie użytkownikami (tylko `ROLE_ADMIN`, atrybut `RequiresRole('ROLE_ADMIN')`)

| Metoda | Ścieżka | Opis |
|---|---|---|
| `GET` | `/api/admin/users` | Lista użytkowników (bez `deletedAt != null`) |
| `POST` | `/api/admin/users` | Utwórz użytkownika |
| `GET` | `/api/admin/users/{id}` | Szczegóły użytkownika |
| `PATCH` | `/api/admin/users/{id}` | Edytuj (imię, nazwisko, rola, active) |
| `POST` | `/api/admin/users/{id}/reset-password` | Ustaw nowe hasło |
| `DELETE` | `/api/admin/users/{id}` | Soft-delete |

**Request POST /api/admin/users:**
```json
{ "email": "anna@example.com", "firstName": "Anna", "lastName": "Nowak", "role": "ROLE_EMPLOYEE", "password": "tajneHaslo123" }
```

**Request PATCH /api/admin/users/{id}** — wszystkie pola opcjonalne:
```json
{ "firstName": "Anna", "lastName": "Nowak", "role": "ROLE_ADMIN", "active": false }
```

**Request POST /api/admin/users/{id}/reset-password:**
```json
{ "password": "noweHaslo456" }
```

### Istniejące endpointy — bez zmian

- `GET /api/vin/ford/{vin}` — nadal wymaga `X-License-Key`
- `/api/admin/licenses/*` — nadal wymaga `X-Admin-Key`

## Konfiguracja Symfony Security

`config/packages/security.yaml`:

```yaml
security:
  password_hashers:
    App\User\Domain\User: bcrypt

  providers:
    users:
      entity:
        class: App\User\Domain\User
        property: email

  firewalls:
    main:
      stateless: true
      provider: users
      json_login:
        check_path: /api/auth/login
        username_path: email
        password_path: password
        success_handler: lexik_jwt_authentication.handler.authentication_success
        failure_handler: lexik_jwt_authentication.handler.authentication_failure
      jwt: ~

  access_control: []
```

## Konfiguracja JWT

`config/packages/lexik_jwt_authentication.yaml`:

```yaml
lexik_jwt_authentication:
  secret_key: '%env(JWT_SECRET_KEY)%'
  public_key: '%env(JWT_PUBLIC_KEY)%'
  pass_phrase: '%env(JWT_PASSPHRASE)%'
  token_ttl: 3600
```

Nowe zmienne środowiskowe w `.env`:

```
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=change-me-jwt-passphrase
CORS_ALLOW_ORIGIN=http://localhost:5173
```

Klucze RSA generowane przez skrypt w `docker/` przy starcie kontenera (`openssl genrsa`).

## CORS

`nelmio/cors-bundle` skonfigurowany dla `CORS_ALLOW_ORIGIN`, zakres `/api/*`.

## Komenda CLI

```bash
php bin/console app:create-admin --email=admin@example.com --firstName=Jan --lastName=Kowalski --password=tajneHaslo123
```

Tworzy użytkownika z `ROLE_ADMIN` i aktywnym kontem. Używana do bootstrapowania pierwszego admina.

## Weryfikacja ról

`AuthorizationListener` rozszerzony:

- `RequiresAdmin` → sprawdza `X-Admin-Key` (bez zmian)
- `RequiresLicense` → sprawdza `X-License-Key` (bez zmian)
- `RequiresRole('ROLE_ADMIN')` → weryfikuje JWT + sprawdza rolę przez `Security::getUser()`

Symfony Security automatycznie weryfikuje sygnaturę JWT dla każdego żądania z `Authorization: Bearer <token>`.

## Testowanie

### Testy jednostkowe (`tests/User/`)

- `UserTest` — logika domeny: `softDelete()`, `isActive()`, `touch()`, role
- `UserManagerTest` — tworzenie użytkownika, hashowanie hasła (mock), walidacja duplikatu emaila
- `CreateAdminCommandTest` — komenda tworzy admina z `ROLE_ADMIN`

### Testy integracyjne (`tests/Controller/`)

- `AuthControllerTest` — login z poprawnymi/błędnymi danymi; `/api/auth/me` z ważnym/wygasłym tokenem
- `UserAdminControllerTest` — pełny CRUD; weryfikacja że `ROLE_EMPLOYEE` dostaje `403`

### Stub testowy

`tests/User/Application/InMemoryUserRepository.php` — implementuje `UserRepositoryInterface` dla testów jednostkowych (analogia do `InMemoryLicenseLookup`).

### Konwencje

- PHP 8.2+, `declare(strict_types=1)` w każdym pliku
- `final readonly` dla serwisów domenowych
- PHPStan level 9, PSR-12
- Wszystkie nowe klasy objęte przez `make check`
