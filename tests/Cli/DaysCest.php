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
        $I->runShellCommand("DATA_JSON=tests/Support/Data/new-year.json WEBHOOK_URL= php index.php --date='2030-12-31 08:00'");
        $I->seeInShellOutput('Nyårsafton is today!');
        $I->seeInShellOutput('Nyårsdagen is tomorrow!');
    }

    // A failed Slack post must fail the run, or the scheduled workflow stays green.
    public function failedPost(CliTester $I): void
    {
        // port 9 (discard) refuses the connection, so curl fails
        $I->runShellCommand("DATA_JSON=tests/Support/Data/new-year.json WEBHOOK_URL=http://127.0.0.1:9/ php index.php --date='2030-12-31 08:00'", false);
        $I->seeResultCodeIsNot(0);
    }

    // An HTTP error from Slack must fail the run too, not only a refused connection.
    public function httpErrorPost(CliTester $I): void
    {
        $server = proc_open(['php', '-S', '127.0.0.1:8765', 'tests/Support/http-500.php'], [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']], $pipes);
        for ($i = 0; $i < 50 && !str_contains($status = (@get_headers('http://127.0.0.1:8765/') ?: [''])[0], ' 500 '); $i++) {
            usleep(100000);
        }

        try {
            // without this, a missing or foreign server would turn the test into a refused-connection or 404 test
            Assert::assertStringContainsString(' 500 ', $status, 'tests/Support/http-500.php is not answering on 127.0.0.1:8765');
            $I->runShellCommand("DATA_JSON=tests/Support/Data/new-year.json WEBHOOK_URL=http://127.0.0.1:8765/ php index.php --date='2030-12-31 08:00'", false);
            $I->seeResultCodeIsNot(0);
            $I->seeInShellOutput('Posting to Slack failed.');
        } finally {
            proc_terminate($server);
        }
    }

    // Runs index.php with the fake curl from tests/Support/bin and returns the posted payload.
    private function posted(CliTester $I, string $date, string $env = ''): array
    {
        @unlink('tests/_output/curl-data.json');
        @unlink('tests/_output/curl-args.txt');
        $I->runShellCommand("$env PATH=tests/Support/bin:\$PATH DATA_JSON=tests/Support/Data/quotes.json WEBHOOK_URL=http://example.invalid/ php index.php --date='$date 08:00'", false);
        $I->seeResultCodeIs(0);

        return json_decode((string) @file_get_contents('tests/_output/curl-data.json'), true) ?? [];
    }

    // Returns the text of the posted message.
    private function postedText(CliTester $I, string $date): string
    {
        return $this->posted($I, $date)['blocks'][0]['text']['text'] ?? '';
    }

    // Returns the text under the message: repo link and version.
    private function postedContext(CliTester $I, string $date, string $env = ''): string
    {
        return $this->posted($I, $date, $env)['blocks'][2]['elements'][0]['text'] ?? '';
    }

    // The link goes on its own line under the day.
    public function linkOnOwnLine(CliTester $I): void
    {
        Assert::assertSame(":warning: Idag är det *Våffeldagen (it's waffle day)*\nx", $this->postedText($I, '2030-03-25'));
    }

    // The message says which app and which commit sent it.
    public function repoLinkAndVersion(CliTester $I): void
    {
        $sha = trim((string) shell_exec('git rev-parse --short HEAD'));
        Assert::assertSame(
            "<https://github.com/fiskhandlarn/BAJSMACKANS-DAG|BAJSMACKANS-DAG> · <https://github.com/fiskhandlarn/BAJSMACKANS-DAG/commit/$sha|$sha>",
            $this->postedContext($I, '2030-03-25'),
        );
    }

    // Without a git repo, the version reads 'dev'.
    public function versionWithoutGit(CliTester $I): void
    {
        Assert::assertStringEndsWith(' · dev', $this->postedContext($I, '2030-03-25', 'GIT_DIR=/nonexistent'));
    }

    // A ' in a title must not break the shell command that runs curl.
    public function apostropheInTitle(CliTester $I): void
    {
        Assert::assertStringContainsString("Våffeldagen (it's waffle day)", $this->postedText($I, '2030-03-25'));
    }

    // A " in a title must not break the JSON payload.
    public function doubleQuoteInTitle(CliTester $I): void
    {
        Assert::assertStringContainsString('The "best" bun day', $this->postedText($I, '2030-03-27'));
    }

    // A Slack that accepts the connection but never answers must not block the run until the job limit.
    public function curlTimeouts(CliTester $I): void
    {
        $this->postedText($I, '2030-03-25');
        $args = (string) @file_get_contents('tests/_output/curl-args.txt');
        Assert::assertStringContainsString("--connect-timeout\n10\n", $args);
        Assert::assertStringContainsString("--max-time\n30\n", $args);
    }

    // GitHub's runner PHP has no E in variables_order, so process env vars never reach $_ENV.
    public function processEnvWithoutEInVariablesOrder(CliTester $I): void
    {
        // port 9 (discard) refuses the connection, so nothing is posted
        $I->runShellCommand("DATA_JSON=tests/Support/Data/new-year.json WEBHOOK_URL=http://127.0.0.1:9/ php -d variables_order=GPCS index.php --date='2030-12-31 08:00'", false);
        $I->seeInShellOutput('Nyårsafton is today!');
        $I->dontSeeInShellOutput('No webhook URL found.');
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
