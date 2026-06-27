<?php

declare(strict_types=1);

namespace App\Tests\Pid\Application;

use App\Pid\Application\Dto\KnownPidWriteRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class KnownPidWriteRequestTest extends TestCase
{
    public function testItCreatesDtoFromValidRequest(): void
    {
        $dto = KnownPidWriteRequest::fromRequest($this->jsonRequest([
            'model' => 'Focus MK3',
            'pid' => '22f40d',
            'name' => 'Oil temperature',
            'unit' => '°C',
            'description' => '',
            'active' => true,
        ]));

        self::assertInstanceOf(KnownPidWriteRequest::class, $dto);
        self::assertSame('Focus MK3', $dto->model);
        self::assertSame('22F40D', $dto->pid);
        self::assertSame('Oil temperature', $dto->name);
        self::assertNull($dto->description);
        self::assertTrue($dto->active);
    }

    public function testItRejectsInvalidPid(): void
    {
        $result = KnownPidWriteRequest::fromRequest($this->jsonRequest([
            'model' => 'Focus MK3',
            'pid' => '22 F40D',
            'name' => 'Oil temperature',
        ]));

        self::assertSame('invalid_pid', $result);
    }

    public function testItUsesActiveTrueByDefault(): void
    {
        $dto = KnownPidWriteRequest::fromRequest($this->jsonRequest([
            'model' => 'Focus MK3',
            'pid' => '22F40D',
            'name' => 'Oil temperature',
        ]));

        self::assertInstanceOf(KnownPidWriteRequest::class, $dto);
        self::assertTrue($dto->active);
    }

    /** @param array<string, mixed> $data */
    private function jsonRequest(array $data): Request
    {
        return new Request(content: (string) json_encode($data));
    }
}
