<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;

require_once __DIR__ . '/../../src/days.php';

class ResolveDayTest extends Unit
{
    // Expected dates are hard-coded so a bug in resolveDay() can't hide behind itself.
    public static function days(): array
    {
        return [
            // Fettisdagen, from https://sv.wikipedia.org/wiki/Fettisdagen
            ['47 days before easter', 2025, '2025-03-04'],
            ['47 days before easter', 2026, '2026-02-17'],
            ['47 days before easter', 2027, '2027-02-09'],
            ['47 days before easter', 2028, '2028-02-29'],
            ['47 days before easter', 2029, '2029-02-13'],
            ['january 10', 2026, '2026-01-10'],
            ['7 march', 2027, '2027-03-07'],
            ['18 dec', 2026, '2026-12-18'],
            ['first thursday of march', 2026, '2026-03-05'],
            ['first thursday of march', 2027, '2027-03-04'],
            ['last monday of september', 2026, '2026-09-28'],
        ];
    }

    #[DataProvider('days')]
    public function testResolves(string $day, int $year, string $expected): void
    {
        $carbon = resolveDay($day, $year);

        $this->assertSame($expected, $carbon->toDateString());
        $this->assertSame(TIMEZONE, $carbon->getTimezone()->getName());
    }

    public function testUnparseableIsNull(): void
    {
        $this->assertNull(resolveDay('5 dayz before easter', 2026));
        $this->assertNull(resolveDay('47 days before easter_dat', 2026));
        $this->assertNull(resolveDay('januray 10', 2026));
    }
}
