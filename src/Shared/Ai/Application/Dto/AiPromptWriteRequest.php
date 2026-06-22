<?php

declare(strict_types=1);

namespace App\Shared\Ai\Application\Dto;

use App\Shared\Ai\Domain\AiPrompt;
use Symfony\Component\HttpFoundation\Request;

final readonly class AiPromptWriteRequest
{
    public function __construct(
        public string $name,
        public string $prompt,
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
    public static function fromPatchRequest(Request $request, AiPrompt $existing): self|string
    {
        $incoming = self::parseJson($request);

        if (is_string($incoming)) {
            return $incoming;
        }

        return self::validate([
            'name' => $existing->getName(),
            'prompt' => $existing->getPrompt(),
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
        $name = trim(self::str($data['name'] ?? ''));
        $prompt = trim(self::str($data['prompt'] ?? ''));

        if ($name === '') {
            return 'missing_name';
        }

        if ($prompt === '') {
            return 'missing_prompt';
        }

        return new self($name, $prompt);
    }

    private static function str(mixed $value): string
    {
        return is_string($value) ? $value : (string) $value; // @phpstan-ignore cast.string
    }
}
