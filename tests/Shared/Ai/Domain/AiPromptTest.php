<?php

declare(strict_types=1);

namespace App\Tests\Shared\Ai\Domain;

use App\Shared\Ai\Domain\AiPrompt;
use PHPUnit\Framework\TestCase;

final class AiPromptTest extends TestCase
{
    public function testNewPromptStoresNameAndPrompt(): void
    {
        $prompt = new AiPrompt('VIN analysis', 'Analyze this VIN.');

        self::assertNull($prompt->getId());
        self::assertSame('VIN analysis', $prompt->getName());
        self::assertSame('Analyze this VIN.', $prompt->getPrompt());
    }

    public function testUpdateChangesNameAndPrompt(): void
    {
        $prompt = new AiPrompt('Old', 'Old prompt.');

        $prompt->update('New', 'New prompt.');

        self::assertSame('New', $prompt->getName());
        self::assertSame('New prompt.', $prompt->getPrompt());
    }
}
