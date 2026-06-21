<?php

declare(strict_types=1);

namespace App\License\Application\Dto;

use App\License\Domain\License;
use Symfony\Component\HttpFoundation\Request;

final readonly class LicenseWriteRequest
{
    public function __construct(
        public string $clientName,
        public string $clientEmail,
        public ?string $note,
        public \DateTimeImmutable $validUntil,
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
    public static function fromPatchRequest(Request $request, License $existing): self|string
    {
        $incoming = self::parseJson($request);

        if (is_string($incoming)) {
            return $incoming;
        }

        $data = [
            'clientName'  => $existing->getClientName(),
            'clientEmail' => $existing->getClientEmail(),
            'note'        => $existing->getNote(),
            'validUntil'  => $existing->getValidUntil()->format(\DATE_ATOM),
            'active'      => $existing->isActive(),
            ...$incoming,
        ];

        return self::validate($data, requireActive: true);
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
    private static function validate(array $data, bool $requireActive = false): self|string
    {
        foreach (['clientName', 'clientEmail', 'validUntil'] as $field) {
            $value = $data[$field] ?? null;
            if ($value === null || trim(self::str($value)) === '') {
                return sprintf('missing_%s', $field);
            }
        }

        if (!filter_var($data['clientEmail'], \FILTER_VALIDATE_EMAIL)) {
            return 'invalid_clientEmail';
        }

        try {
            $validUntil = new \DateTimeImmutable(self::str($data['validUntil']));
        } catch (\Exception) {
            return 'invalid_validUntil';
        }

        if ($requireActive && !isset($data['active'])) {
            return 'missing_active';
        }

        if ($requireActive && !is_bool($data['active'])) {
            return 'invalid_active';
        }

        return new self(
            clientName: trim(self::str($data['clientName'])),
            clientEmail: trim(self::str($data['clientEmail'])),
            note: isset($data['note']) ? trim(self::str($data['note'])) : null,
            validUntil: $validUntil,
            active: $requireActive ? (bool) $data['active'] : null,
        );
    }

    private static function str(mixed $value): string
    {
        return is_string($value) ? $value : (string) $value; // @phpstan-ignore cast.string
    }
}
