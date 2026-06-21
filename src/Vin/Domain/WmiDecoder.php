<?php

declare(strict_types=1);

namespace App\Vin\Domain;

final class WmiDecoder
{
    private const FORD_WMI = [
        '1FA' => 'Ford USA (Passenger)',
        '1FB' => 'Ford USA (Van/Bus)',
        '1FC' => 'Ford USA (Commercial)',
        '1FD' => 'Ford USA (Incomplete Vehicle)',
        '1FM' => 'Ford USA (SUV/MPV)',
        '1FT' => 'Ford USA (Truck/Pickup)',
        '1L1' => 'Lincoln USA',
        '1ME' => 'Mercury USA',
        '2FA' => 'Ford Canada',
        '2FM' => 'Ford Canada',
        '3FA' => 'Ford Mexico',
        '3FM' => 'Ford Mexico',
        'SFA' => 'Ford Motor Company United Kingdom',
        'VS6' => 'Ford Spain',
        'WF0' => 'Ford Europe',
        'NM0' => 'Ford Otosan Turkey',
        'MAJ' => 'Ford India',
    ];

    public function getWmi(string $vin): ?string
    {
        $vin = strtoupper(trim($vin));

        return strlen($vin) >= 3 ? substr($vin, 0, 3) : null;
    }

    public function decodeManufacturer(string $vin): ?string
    {
        $wmi = $this->getWmi($vin);

        return $wmi !== null ? self::FORD_WMI[$wmi] ?? null : null;
    }

    public function requiresCheckDigit(string $vin): bool
    {
        $vin = strtoupper(trim($vin));

        if (strlen($vin) < 1) {
            return false;
        }

        return in_array($vin[0], ['1', '2', '3', '4', '5'], true);
    }
}
