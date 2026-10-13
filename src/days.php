<?php

declare(strict_types=1);

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

const TIMEZONE = 'Europe/Stockholm';

// Resolves a "day" string from the data file to a date in $year, or null if it can't be resolved.
function resolveDay(string $day, int $year): ?Carbon
{
    if (false !== strpos($day, 'before')) {
        if (!preg_match('#(\d+ days) before (\w+\b)#', $day, $matches)) {
            return null;
        }

        return Carbon::createFromTimestamp(call_user_func($matches[2], $year), TIMEZONE)->sub(\DateInterval::createFromDateString($matches[1]));
    }

    try {
        return new Carbon("$day $year", TIMEZONE);
    } catch (InvalidFormatException) {
        return null;
    }
}
