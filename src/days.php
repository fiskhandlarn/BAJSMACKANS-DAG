<?php

declare(strict_types=1);

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

const TIMEZONE = 'Europe/Stockholm';

// Resolves a day rule from the calendar to a date in $year, or null if it
// can't be resolved.
function resolveDayRule(string $dayRule, int $year): ?Carbon
{
    if (preg_match('#^(\d+) days before easter$#', $dayRule, $matches)) {
        // easter_days() is the number of days from 21 March to Easter Sunday,
        // 21 March being the start point of the Easter rule (first Sunday after
        // the first full moon on or after 21 March).
        return Carbon::create($year, 3, 21, 0, 0, 0, TIMEZONE)
            ->addDays(easter_days($year) - (int) $matches[1]);
    }

    try {
        return new Carbon("$dayRule $year", TIMEZONE);
    } catch (InvalidFormatException) {
        return null;
    }
}
