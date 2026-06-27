# Smart Diag Backend — Symfony + Docker

Backend API for AI-powered diagnostics of Ford vehicles: VIN decoding, fault code analysis, and repair guidance.

## Features

- **VIN decoding** — format validation, check digit, WMI, model year, plant code, serial number (North American and European Ford VINs)
- **NHTSA enrichment** — optional external lookup via the vPIC API (cached 24 h in Redis)
- **JWT authentication** — login with email/password, role-based access (`ROLE_ADMIN`, `ROLE_EMPLOYEE`)
- **User administration** — create, list, update, soft-delete users and reset passwords (admin only)
- **License management** — issue and manage API license keys (admin only)
- **Known PID management** — per-model OBD-II PID catalogue (name, unit, description, active flag); admin CRUD + license-key read endpoint
- **AI prompt management** — named prompt templates stored in the database; admin CRUD (create, read, update, delete)
- **AI chat completions** — GitHub AI Models client (`GitHubAiClient`) backed by `models.github.ai`; default model `meta/Meta-Llama-3.1-405B-Instruct`

## Running

```bash
docker compose up --build
make migrate
```

The API is available at `http://localhost:8000`.

## API Endpoints

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/health` | — | Health check |
| `POST` | `/api/auth/login` | — | Log in, receive JWT |
| `GET` | `/api/auth/me` | JWT | Current user info |
| `GET` | `/api/vin/ford/{vin}` | License key | Decode a Ford VIN |
| `GET` | `/api/admin/users` | JWT + ROLE_ADMIN | List users |
| `POST` | `/api/admin/users` | JWT + ROLE_ADMIN | Create a user |
| `GET` | `/api/admin/users/{id}` | JWT + ROLE_ADMIN | Get a user |
| `PATCH` | `/api/admin/users/{id}` | JWT + ROLE_ADMIN | Update a user |
| `POST` | `/api/admin/users/{id}/reset-password` | JWT + ROLE_ADMIN | Reset password |
| `DELETE` | `/api/admin/users/{id}` | JWT + ROLE_ADMIN | Soft-delete a user |
| `GET` | `/api/admin/licenses` | Admin key | List licenses |
| `POST` | `/api/admin/licenses` | Admin key | Create a license |
| `GET` | `/api/admin/licenses/{id}` | Admin key | Get a license |
| `PATCH` | `/api/admin/licenses/{id}` | Admin key | Update a license |
| `DELETE` | `/api/admin/licenses/{id}` | Admin key | Delete a license |
| `GET` | `/api/known-pids/{model}` | License key | List known PIDs for a model |
| `GET` | `/api/admin/known-pids` | JWT + ROLE_ADMIN | List known PIDs (admin) |
| `POST` | `/api/admin/known-pids` | JWT + ROLE_ADMIN | Create a known PID |
| `PATCH` | `/api/admin/known-pids/{id}` | JWT + ROLE_ADMIN | Update a known PID |
| `DELETE` | `/api/admin/known-pids/{id}` | JWT + ROLE_ADMIN | Delete a known PID |
| `GET` | `/api/admin/ai-prompts` | JWT + ROLE_ADMIN | List AI prompts |
| `POST` | `/api/admin/ai-prompts` | JWT + ROLE_ADMIN | Create an AI prompt |
| `GET` | `/api/admin/ai-prompts/{id}` | JWT + ROLE_ADMIN | Get an AI prompt |
| `PATCH` | `/api/admin/ai-prompts/{id}` | JWT + ROLE_ADMIN | Update an AI prompt |
| `DELETE` | `/api/admin/ai-prompts/{id}` | JWT + ROLE_ADMIN | Delete an AI prompt |

Full interactive documentation (Swagger UI): `http://localhost:8000/docs`
OpenAPI spec: `http://localhost:8000/docs/openapi.json`

## Quick Start

**Create the first admin account:**

```bash
docker compose run --rm app php bin/console app:create-admin \
  --email=admin@example.com \
  --firstName=Jan \
  --lastName=Kowalski \
  --password=secret123
```

**Log in and get a JWT:**

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"secret123"}'
```

**Decode a VIN (requires a license key):**

```bash
curl -H "X-License-Key: <key>" http://localhost:8000/api/vin/ford/1FA6P8CF0F5300000
# with NHTSA enrichment:
curl -H "X-License-Key: <key>" "http://localhost:8000/api/vin/ford/1FA6P8CF0F5300000?external=1"
```

**Create a license (requires admin key):**

```bash
curl -X POST http://localhost:8000/api/admin/licenses \
  -H "X-Admin-Key: docker-admin-secret" \
  -H "Content-Type: application/json" \
  -d '{"clientName":"ACME","clientEmail":"admin@example.com","validUntil":"2027-06-21T23:59:59+00:00"}'
```

## Make Commands

```bash
make up        # build and start the stack
make down      # stop containers
make shell     # shell in the app container
make migrate   # run Doctrine migrations
make test      # PHPUnit
make analyse   # PHPStan (level 6)
make cs        # PHP_CodeSniffer (PSR-12)
make check     # test + analyse + cs
```

## Environment Variables

| Variable | Default | Purpose |
|----------|---------|---------|
| `DATABASE_URL` | `postgresql://vin:vin@db:5432/vin` | PostgreSQL 16 |
| `REDIS_URL` | `redis://redis:6379` | Redis cache |
| `ADMIN_API_KEY` | `change-me-admin-key` | License admin endpoints |
| `JWT_PASSPHRASE` | `change-me-jwt-passphrase` | JWT key passphrase |
| `AI_GITHUB_TOKEN` | *(required)* | GitHub AI Models API token |

## Project Structure

```text
src/
  Controller/Api/          — HTTP layer (routes, OA attributes)
  Vin/
    Domain/                — pure VIN decoding rules
    Application/           — DecodeFordVin service, Redis caching
    Infrastructure/Nhtsa/  — NHTSA vPIC client
  License/
    Domain/                — License entity (soft delete)
    Application/           — LicenseValidator, LicenseKeyGenerator
    Infrastructure/        — LicenseRepository
  User/
    Domain/                — User entity (roles, soft delete)
    Application/           — UserManager, UserLookup
    Command/               — app:create-admin console command
  Shared/Auth/
    Attribute/             — #[RequiresAuth], #[RequiresRole], …
    EventListener/         — JWT success / authorization listeners
  Shared/Ai/
    Domain/                — AiPrompt entity
    Application/           — prompt DTOs
    Infrastructure/GitHub/ — GitHubAiClient, GitHubAiMessage, GitHubAiResponse
  Pid/
    Domain/                — KnownPid entity
    Application/           — KnownPidWriteRequest DTO
    Infrastructure/        — KnownPidRepository
tests/
```

## Useful URLs

| URL | Description |
|-----|-------------|
| `http://localhost:8000/docs` | Swagger UI |
| `http://localhost:8000/docs/openapi.json` | OpenAPI spec |
| `http://localhost:8000/_profiler` | Symfony web profiler (dev only) |
| `http://localhost:8081` | Redis browser |
