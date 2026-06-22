<?php

declare(strict_types=1);

namespace App\Tests\Shared\Ai\Application\Dto;

use App\Shared\Ai\Application\Dto\AiPromptWriteRequest;
use App\Shared\Ai\Domain\AiPrompt;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class AiPromptWriteRequestTest extends TestCase
{
    public function testFromRequestCreatesDto(): void
    {
        $dto = AiPromptWriteRequest::fromRequest($this->jsonRequest([
            'name' => 'VIN analysis',
            'prompt' => 'Analyze VIN data.',
        ]));

        self::assertInstanceOf(AiPromptWriteRequest::class, $dto);
        self::assertSame('VIN analysis', $dto->name);
        self::assertSame('Analyze VIN data.', $dto->prompt);
    }

    public function testFromRequestReturnsErrorForMissingPrompt(): void
    {
        $dto = AiPromptWriteRequest::fromRequest($this->jsonRequest(['name' => 'VIN analysis']));

        self::assertSame('missing_prompt', $dto);
    }

    public function testFromPatchRequestKeepsExistingValues(): void
    {
        $existing = new AiPrompt('Old name', 'Old prompt.');

        $dto = AiPromptWriteRequest::fromPatchRequest($this->jsonRequest(['name' => 'New name']), $existing);

        self::assertInstanceOf(AiPromptWriteRequest::class, $dto);
        self::assertSame('New name', $dto->name);
        self::assertSame('Old prompt.', $dto->prompt);
    }

    /** @param array<string, mixed> $data */
    private function jsonRequest(array $data): Request
    {
        return new Request(content: (string) json_encode($data));
    }
}
