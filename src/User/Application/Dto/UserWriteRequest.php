<?php

declare(strict_types=1);

namespace App\User\Application\Dto;

use App\User\Domain\User;
use Symfony\Component\HttpFoundation\Request;

final readonly class UserWriteRequest
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $role,
        public string $password,
        public ?bool $active = null,
    ) {
    }

    /**
     * @return self|non-empty-string
     */
    public static function fromRequest(Request $request): self|string
    {
        $data = self::parseJson($request);

        if (is_string($data)) {
            return $data;
        }

        return self::validate($data);
    }

    /**
     * @return self|non-empty-string
     */
    public static function fromPatchRequest(Request $request, User $existing): self|string
    {
        $incoming = self::parseJson($request);

        if (is_string($incoming)) {
            return $incoming;
        }

        $data = [
            'email'     => $existing->getEmail(),
            'firstName' => $existing->getFirstName(),
            'lastName'  => $existing->getLastName(),
            'role'      => $existing->getRole(),
            'password'  => '__PATCH__',
            'active'    => $existing->isActive(),
            ...$incoming,
        ];

        return self::validate($data, isPatch: true);
    }

    /**
     * @return array<string, mixed>|non-empty-string
     */
    private static function parseJson(Request $request): array|string
    {
        try {
            $raw = json_decode($request->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return 'invalid_json';
        }

        if (!is_array($raw)) {
            return 'invalid_json';
        }

        /** @var array<string, mixed> $raw */
        return $raw;
    }

    /**
     * @param array<string, mixed> $data
     * @return self|non-empty-string
     */
    private static function validate(array $data, bool $isPatch = false): self|string
    {
        $email     = trim((string) ($data['email'] ?? ''));
        $firstName = trim((string) ($data['firstName'] ?? ''));
        $lastName  = trim((string) ($data['lastName'] ?? ''));
        $role      = trim((string) ($data['role'] ?? ''));
        $password  = (string) ($data['password'] ?? '');
        $active    = isset($data['active']) ? (bool) $data['active'] : null;

        if ($email === '') {
            return 'missing_email';
        }

        if ($firstName === '') {
            return 'missing_first_name';
        }

        if ($lastName === '') {
            return 'missing_last_name';
        }

        if (!in_array($role, ['ROLE_ADMIN', 'ROLE_EMPLOYEE'], true)) {
            return 'invalid_role';
        }

        if (!$isPatch && strlen($password) < 8) {
            return 'password_too_short';
        }

        return new self($email, $firstName, $lastName, $role, $password, $active);
    }
}
