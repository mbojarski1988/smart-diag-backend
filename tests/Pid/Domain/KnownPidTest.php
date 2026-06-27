<?php

declare(strict_types=1);

namespace App\Tests\Pid\Domain;

use App\Pid\Domain\KnownPid;
use PHPUnit\Framework\TestCase;

final class KnownPidTest extends TestCase
{
    public function testItNormalizesModelAndPid(): void
    {
        $knownPid = new KnownPid(
            model: '  Focus   MK3  ',
            pid: ' 22f40d ',
            name: 'Oil temperature',
            unit: ' °C ',
            description: ' Oil temp ',
        );

        self::assertSame('Focus MK3', $knownPid->getModel());
        self::assertSame('22F40D', $knownPid->getPid());
        self::assertSame('°C', $knownPid->getUnit());
        self::assertSame('Oil temp', $knownPid->getDescription());
    }

    public function testItSerializesToArray(): void
    {
        $knownPid = new KnownPid(
            model: 'Mondeo MK5',
            pid: '221234',
            name: 'Battery voltage',
            unit: 'V',
            description: null,
            active: false,
        );

        self::assertSame([
            'id' => null,
            'model' => 'Mondeo MK5',
            'pid' => '221234',
            'name' => 'Battery voltage',
            'unit' => 'V',
            'description' => null,
            'active' => false,
        ], $knownPid->toArray());
    }
}
