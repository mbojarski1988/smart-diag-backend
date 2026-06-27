<?php

declare(strict_types=1);

namespace App\Pid\Application\Dto;

use App\Pid\Domain\KnownPid;
use Symfony\Component\HttpFoundation\Request;

final readonly class KnownPidWriteRequest
{
    public function __construct(
        public string $model,
        public string $pid,
        public string $name,
        public ?string $unit,
        public ?string $description,
        public bool $active,
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
    public static function fromPatchRequest(Request $request, KnownPid $existing): self|string
    {
        $incoming = self::parseJson($request);

        if (is_string($incoming)) {
            return $incoming;
        }

        return self::validate([
            'model' => $existing->getModel(),
            'pid' => $existing->getPid(),
            'name' => $existing->getName(),
            'unit' => $existing->getUnit(),
            'description' => $existing->getDescription(),
            'active' => $existing->isActive(),
            ...$incoming,
        ]);
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
    private static function validate(array $data): self|string
    {
        $model = KnownPid::normalizeModel(self::str($data['model'] ?? ''));
        $pid = KnownPid::normalizePid(self::str($data['pid'] ?? ''));
        $name = trim(self::str($data['name'] ?? ''));

        if ($model === '') {
            return 'missing_model';
        }

        if ($pid === '') {
            return 'missing_pid';
        }

        if (!preg_match('/^[A-Z0-9._:-]{1,40}$/', $pid)) {
            return 'invalid_pid';
        }

        if ($name === '') {
            return 'missing_name';
        }

        $active = $data['active'] ?? true;

        if (!is_bool($active)) {
            return 'invalid_active';
        }

        return new self(
            model: $model,
            pid: $pid,
            name: $name,
            unit: self::nullableStr($data['unit'] ?? null),
            description: self::nullableStr($data['description'] ?? null),
            active: $active,
        );
    }

    private static function str(mixed $value): string
    {
        return is_string($value) ? $value : (string) $value; // @phpstan-ignore cast.string
    }

    private static function nullableStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim(self::str($value));

        return $trimmed === '' ? null : $trimmed;
    }
}
