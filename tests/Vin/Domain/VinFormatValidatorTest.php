<?php

declare(strict_types=1);

namespace App\Tests\Vin\Domain;

use App\Vin\Domain\VinFormatValidator;
use PHPUnit\Framework\TestCase;

final class VinFormatValidatorTest extends TestCase
{
    private VinFormatValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new VinFormatValidator();
    }

    public function testNormalizeUppercasesAndTrims(): void
    {
        self::assertSame('1FA6P8CF0F5300000', $this->validator->normalize('  1fa6p8cf0f5300000  '));
    }

    public function testIsValidAcceptsValid17CharVin(): void
    {
        self::assertTrue($this->validator->isValid('1FA6P8CF0F5300000'));
    }

    public function testIsValidAcceptsEuropeanVin(): void
    {
        self::assertTrue($this->validator->isValid('WF0MXXGCDM8R43240'));
    }

    public function testIsValidReturnsFalseForTooShortVin(): void
    {
        self::assertFalse($this->validator->isValid('1FA6P8CF0F5'));
    }

    public function testIsValidReturnsFalseForTooLongVin(): void
    {
        self::assertFalse($this->validator->isValid('1FA6P8CF0F5300000X'));
    }

    public function testIsValidReturnsFalseForForbiddenLetterI(): void
    {
        self::assertFalse($this->validator->isValid('1FA6P8CF0F530000I'));
    }

    public function testIsValidReturnsFalseForForbiddenLetterO(): void
    {
        self::assertFalse($this->validator->isValid('1FA6P8CF0F530000O'));
    }

    public function testIsValidReturnsFalseForForbiddenLetterQ(): void
    {
        self::assertFalse($this->validator->isValid('1FA6P8CF0F530000Q'));
    }

    public function testIsValidReturnsFalseForEmptyString(): void
    {
        self::assertFalse($this->validator->isValid(''));
    }

    public function testIsValidNormalizesBeforeChecking(): void
    {
        self::assertTrue($this->validator->isValid('  1fa6p8cf0f5300000  '));
    }
}
