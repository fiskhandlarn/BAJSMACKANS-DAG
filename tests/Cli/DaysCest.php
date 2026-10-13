<?php

declare(strict_types=1);

namespace Tests\Cli;

use Carbon\Carbon;
use Codeception\Attribute\DataProvider;
use Codeception\Example;
use PHPUnit\Framework\Assert;
use Tests\Support\CliTester;

require_once __DIR__ . '/../../src/env.php';

// Every entry in DATA_JSON becomes a test case, so new entries are covered automatically.
class DaysCest
{
    private const TIMEZONE = 'Europe/Stockholm'; // same as index.php

    private static function entries(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/../../' . env('DATA_JSON')), true);
    }

    // Mirrors the date resolution in index.php, but returns null instead of skipping.
    private static function resolve(string $day): ?string
    {
        try {
            if (false !== strpos($day, 'before')) {
                if (!preg_match('#(\d+ days) before (\w+\b)#', $day, $matches)) {
                    return null;
                }
                $carbon = Carbon::createFromTimestamp(call_user_func($matches[2]), self::TIMEZONE)->sub(\DateInterval::createFromDateString($matches[1]));
            } else {
                $carbon = new Carbon($day, self::TIMEZONE);
            }
        } catch (\Throwable) {
            return null;
        }

        return $carbon->toDateString();
    }

    protected function days(): array
    {
        $examples = [];
        foreach (self::entries() as $i => $entry) {
            $examples["#$i " . ($entry['title'] ?? '?')] = [
                'entry' => $entry,
                'date' => self::resolve((string) ($entry['day'] ?? '')),
            ];
        }

        return $examples;
    }

    private function runAt(CliTester $I, Example $example, string $modifier): void
    {
        if (null === $example['date']) {
            Assert::fail('unparseable day: ' . ($example['entry']['day'] ?? '(missing)'));
        }

        // cron runs at 08:00; empty WEBHOOK_URL so nothing is posted
        $date = (new Carbon($example['date'] . ' 08:00', self::TIMEZONE))->modify($modifier)->format('Y-m-d H:i');
        $I->runShellCommand('WEBHOOK_URL= php index.php --date=' . escapeshellarg($date));
    }

    #[DataProvider('days')]
    public function fields(CliTester $I, Example $example): void
    {
        foreach (['day', 'title', 'link', 'image'] as $key) {
            Assert::assertIsString($example['entry'][$key] ?? null, "missing $key");
            Assert::assertNotSame('', trim($example['entry'][$key]), "empty $key");
        }
    }

    #[DataProvider('days')]
    public function today(CliTester $I, Example $example): void
    {
        $this->runAt($I, $example, '+0 days');
        $I->seeResultCodeIs(0);
        $I->seeInShellOutput($example['entry']['title'] . ' is today!');
    }

    #[DataProvider('days')]
    public function tomorrow(CliTester $I, Example $example): void
    {
        $this->runAt($I, $example, '-1 day');
        $I->seeResultCodeIs(0);
        $I->seeInShellOutput($example['entry']['title'] . ' is tomorrow!');
    }
}
