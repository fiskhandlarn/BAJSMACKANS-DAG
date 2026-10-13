<?php

declare(strict_types=1);

namespace Tests\Cli;

use Carbon\Carbon;
use Codeception\Attribute\DataProvider;
use Codeception\Example;
use PHPUnit\Framework\Assert;
use Tests\Support\CliTester;

require_once __DIR__ . '/../../src/env.php';
require_once __DIR__ . '/../../src/days.php';

// Every entry in DATA_JSON becomes a test case, so new entries are covered automatically.
class DaysCest
{
    private static function entries(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/../../' . env('DATA_JSON')), true);
    }

    protected function days(): array
    {
        $examples = [];
        foreach (self::entries() as $i => $entry) {
            $examples["#$i " . ($entry['title'] ?? '?')] = [
                'entry' => $entry,
                'date' => resolveDay((string) ($entry['day'] ?? ''), Carbon::now(TIMEZONE)->year)?->toDateString(),
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
        $date = (new Carbon($example['date'] . ' 08:00', TIMEZONE))->modify($modifier)->format('Y-m-d H:i');
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

    // A year other than the current one, crossing into the next year.
    public function newYearsEve(CliTester $I): void
    {
        // variables_order=EGPCS so the DATA_JSON override reaches $_ENV
        $I->runShellCommand("DATA_JSON=tests/Support/Data/new-year.json WEBHOOK_URL= php -d variables_order=EGPCS index.php --date='2030-12-31 08:00'");
        $I->seeInShellOutput('Nyårsafton is today!');
        $I->seeInShellOutput('Nyårsdagen is tomorrow!');
    }

    // A failed Slack post must fail the run, or the scheduled workflow stays green.
    public function failedPost(CliTester $I): void
    {
        // port 9 (discard) refuses the connection, so curl fails
        $I->runShellCommand("DATA_JSON=tests/Support/Data/new-year.json WEBHOOK_URL=http://127.0.0.1:9/ php -d variables_order=EGPCS index.php --date='2030-12-31 08:00'", false);
        $I->seeResultCodeIsNot(0);
    }

    public function noDuplicateTitles(CliTester $I): void
    {
        $seen = [];
        foreach (self::entries() as $entry) {
            // "Foo :coffee:" and "foo" are the same event
            $seen[] = trim(mb_strtolower(preg_replace('#:[\w+-]+:#', '', $entry['title'])));
        }

        $duplicates = array_keys(array_filter(array_count_values($seen), fn ($n) => $n > 1));
        Assert::assertSame([], $duplicates, 'duplicate titles');
    }
}
