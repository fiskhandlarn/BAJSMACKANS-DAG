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

// Every temadag in the calendar (DATA_JSON) becomes a test case, so new temadagar are covered automatically.
class DaysCest
{
    private static function calendar(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/../../' . env('DATA_JSON')), true);
    }

    protected function temadagar(): array
    {
        $examples = [];
        foreach (self::calendar() as $i => $temadag) {
            $examples["#$i " . ($temadag['title'] ?? '?')] = [
                'temadag' => $temadag,
                'date' => resolveDayRule((string) ($temadag['day'] ?? ''), Carbon::now(TIMEZONE)->year)?->toDateString(),
            ];
        }

        return $examples;
    }

    private function runAt(CliTester $I, Example $example, string $modifier): void
    {
        if (null === $example['date']) {
            Assert::fail('unparseable day rule: ' . ($example['temadag']['day'] ?? '(missing)'));
        }

        // cron runs at 08:00; empty WEBHOOK_URL so nothing is posted
        $date = (new Carbon($example['date'] . ' 08:00', TIMEZONE))->modify($modifier)->format('Y-m-d H:i');
        $I->runShellCommand('WEBHOOK_URL= php index.php --date=' . escapeshellarg($date));
    }

    #[DataProvider('temadagar')]
    public function fields(CliTester $I, Example $example): void
    {
        foreach (['day', 'title', 'link', 'image'] as $key) {
            Assert::assertIsString($example['temadag'][$key] ?? null, "missing $key");
            Assert::assertNotSame('', trim($example['temadag'][$key]), "empty $key");
        }

        // Slack rejects the whole message when a card's title or subtitle (the link) is over 150 characters
        foreach (['title', 'link'] as $key) {
            Assert::assertLessThanOrEqual(150, mb_strlen($example['temadag'][$key]), "$key over 150 characters");
        }
    }

    #[DataProvider('temadagar')]
    public function today(CliTester $I, Example $example): void
    {
        $this->runAt($I, $example, '+0 days');
        $I->seeResultCodeIs(0);
        $I->seeInShellOutput($example['temadag']['title'] . ' is today!');
    }

    #[DataProvider('temadagar')]
    public function tomorrow(CliTester $I, Example $example): void
    {
        $this->runAt($I, $example, '-1 day');
        $I->seeResultCodeIs(0);
        $I->seeInShellOutput($example['temadag']['title'] . ' is tomorrow!');
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
        // port 9 (discard) refuses the connection, so the post fails
        $I->runShellCommand("DATA_JSON=tests/Support/Data/new-year.json WEBHOOK_URL=http://127.0.0.1:9/ php index.php --date='2030-12-31 08:00'", false);
        $I->seeResultCodeIsNot(0);
    }

    // Runs $test with the php -S $router answering on 127.0.0.1:8765.
    private function withServer(string $router, string $status, callable $test): void
    {
        $server = proc_open(['php', '-S', '127.0.0.1:8765', $router], [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']], $pipes);
        for ($i = 0; $i < 50 && !str_contains($line = (@get_headers('http://127.0.0.1:8765/') ?: [''])[0], " $status "); $i++) {
            usleep(100000);
        }

        try {
            // without this, a missing or foreign server would turn the test into a refused-connection or 404 test
            Assert::assertStringContainsString(" $status ", $line, "$router is not answering on 127.0.0.1:8765");
            $test();
        } finally {
            proc_terminate($server);
            // waits for the server to exit, so the next test can bind the port
            proc_close($server);
        }
    }

    // An HTTP error from Slack must fail the run too, not only a refused connection.
    public function httpErrorPost(CliTester $I): void
    {
        $this->withServer('tests/Support/http-500.php', '500', function () use ($I) {
            $I->runShellCommand("DATA_JSON=tests/Support/Data/new-year.json WEBHOOK_URL=http://127.0.0.1:8765/ php index.php --date='2030-12-31 08:00'", false);
            $I->seeResultCodeIsNot(0);
            $I->seeInShellOutput('Posting to Slack failed.');
        });
    }

    // Runs index.php against a server that saves the request body, and returns the posted payload.
    private function posted(CliTester $I, string $date, string $env = '', string $args = ''): array
    {
        @unlink('tests/_output/request-body.json');
        $this->withServer('tests/Support/save-request.php', '200', function () use ($I, $date, $env, $args) {
            $I->runShellCommand("$env DATA_JSON=tests/Support/Data/quotes.json WEBHOOK_URL=http://127.0.0.1:8765/ php index.php --date='$date 08:00' $args", false);
            $I->seeResultCodeIs(0);
        });

        return json_decode((string) @file_get_contents('tests/_output/request-body.json'), true) ?? [];
    }

    // Returns the body text of the posted card.
    private function postedText(CliTester $I, string $date): string
    {
        return $this->posted($I, $date)['blocks'][0]['body']['text'] ?? '';
    }

    // Returns the text under the message: repo link and version.
    private function postedContext(CliTester $I, string $date, string $env = ''): string
    {
        return $this->posted($I, $date, $env)['blocks'][1]['elements'][0]['text'] ?? '';
    }

    // The day is a card: title, image, link as subtitle, and today or tomorrow in the body.
    public function card(CliTester $I): void
    {
        $card = $this->posted($I, '2030-03-25')['blocks'][0] ?? [];
        Assert::assertSame('card', $card['type'] ?? null);
        Assert::assertSame("Våffeldagen (it's waffle day)", $card['title']['text'] ?? null);
        Assert::assertSame('y', $card['hero_image']['image_url'] ?? null);
        Assert::assertSame('x', $card['subtitle']['text'] ?? null);
        Assert::assertSame(":warning: Idag är det Våffeldagen (it's waffle day)", $card['body']['text'] ?? null);
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

    // When git fails (not installed, broken repo, dubious ownership), the version reads 'dev', not git's error text.
    public function versionWhenGitFails(CliTester $I): void
    {
        Assert::assertStringEndsWith(' · dev', $this->postedContext($I, '2030-03-25', 'GIT_DIR=/nonexistent'));
    }

    // A copy without its own .git, inside another git repo, must not report that repo's SHA.
    public function versionInsideOtherRepo(CliTester $I): void
    {
        $repo = sys_get_temp_dir() . '/bajsmackans-dag-' . uniqid();
        mkdir("$repo/app", 0777, true);
        copy('index.php', "$repo/app/index.php");
        foreach (['src', 'vendor', 'tests'] as $dir) {
            symlink(realpath($dir), "$repo/app/$dir");
        }

        try {
            shell_exec('git -C ' . escapeshellarg($repo) . ' init -q && git -C ' . escapeshellarg($repo) . ' -c user.name=test -c user.email=test@example.invalid commit -q --allow-empty -m init');
            $I->runShellCommand('DATA_JSON=tests/Support/Data/quotes.json php ' . escapeshellarg("$repo/app/index.php") . " --date='2030-03-25 08:00' --debug");
            $I->seeInShellOutput('|BAJSMACKANS-DAG> · dev');
        } finally {
            shell_exec('rm -rf ' . escapeshellarg($repo));
        }
    }

    // --debug previews the payload and never posts, even with a webhook URL set.
    public function debugNeverPosts(CliTester $I): void
    {
        Assert::assertSame([], $this->posted($I, '2030-03-25', '', '--debug'));
        $I->seeInShellOutput('Not posting to Slack in debug mode.');
        $I->dontSeeInShellOutput('No webhook URL found.');
    }

    // A ' in a title must reach Slack intact.
    public function apostropheInTitle(CliTester $I): void
    {
        Assert::assertStringContainsString("Våffeldagen (it's waffle day)", $this->postedText($I, '2030-03-25'));
    }

    // A " in a title must not break the JSON payload.
    public function doubleQuoteInTitle(CliTester $I): void
    {
        Assert::assertStringContainsString('The "best" bun day', $this->postedText($I, '2030-03-27'));
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
        foreach (self::calendar() as $temadag) {
            // "Foo :coffee:" and "foo" are the same temadag
            $seen[] = trim(mb_strtolower(preg_replace('#:[\w+-]+:#', '', $temadag['title'])));
        }

        $duplicates = array_keys(array_filter(array_count_values($seen), fn ($n) => $n > 1));
        Assert::assertSame([], $duplicates, 'duplicate titles');
    }
}
