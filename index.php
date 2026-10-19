<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/env.php';
require_once __DIR__ . '/src/days.php';
require_once __DIR__ . '/src/slack.php';

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

error_reporting(E_ALL & ~E_DEPRECATED);

date_default_timezone_set(TIMEZONE);

$calendarJSON = @file_get_contents(__DIR__ . '/' . env('DATA_JSON'));

// a calendar error must fail the run, or the scheduled workflow stays green
// while posting nothing
if (false === $calendarJSON) {
    fwrite(STDERR, env('DATA_JSON') . " not found.\n");
    exit(1);
}

$calendar = @json_decode($calendarJSON, true);

// a calendar is a non-empty list of temadagar
if (!is_array($calendar) || !$calendar || !array_is_list($calendar)) {
    fwrite(STDERR, "Invalid JSON.\n");
    exit(1);
}

$now = new Carbon('now', TIMEZONE);
$isDebug = false;

if (php_sapi_name() === "cli") {
    global $argv;

    foreach ($argv as $argument) {
        if ($argument !== $argv[0]) {
            if ('--debug' === $argument) {
                $isDebug = true;
            }

            $pair = explode("=", $argument);
            if (2 === count($pair)) {
                $variableName = $pair[0];
                $variableValue = $pair[1];

                if ('--date' === $variableName) {
                    $now = new Carbon($variableValue);
                }
            }
        }
    }
}

$tomorrow = new Carbon($now . '+ 1 day', TIMEZONE);
$webhookURL = env('WEBHOOK_URL');
$failed = false;
$repoURL = 'https://github.com/fiskhandlarn/BAJSMACKANS-DAG';
// the short SHA of the running commit is the app's version; no git or .git
// gives 'dev'
// git searches parent directories, so without its own .git (a file in a
// worktree) a copy inside another repo would report that repo's SHA
$version = '';
if (file_exists(__DIR__ . '/.git')) {
    $git = 'git -C ' . escapeshellarg(__DIR__);
    $sha = shell_exec("$git rev-parse --short HEAD 2>/dev/null");
    $version = trim((string) $sha);
}
$versionText = $version ? "<$repoURL/commit/$version|$version>" : 'dev';
$footer = "<$repoURL|BAJSMACKANS-DAG> · $versionText";

echo "Checking calendar against " . $now->toDateString()
    . " and " . $tomorrow->toDateString() . ":\n\n";

foreach ($calendar as $idx => $temadag) {
    // resolve against each date's own year so --date and new year's eve work;
    // unparseable day rules resolve to null and never match
    $isToday = $now->format("Y-m-d")
        == resolveDayRule($temadag["day"], $now->year)?->format("Y-m-d");
    $isTomorrow = $tomorrow->format("Y-m-d")
        == resolveDayRule($temadag["day"], $tomorrow->year)?->format("Y-m-d");

    if ($isToday || $isTomorrow) {
        $title = displayTitle($temadag);
        echo "------------------------------------------------------\n";
        echo "🎉 " . $title . ' is '
            . ($isToday ? 'today' : 'tomorrow') . "! 🎉\n\n";
        $when = $isToday ? ':warning: Idag' : ':spiral_calendar_pad: Imorgon';
        $payload = [
            'blocks' => [
                [
                    'type' => 'card',
                    'title' => [
                        'type' => 'mrkdwn',
                        'text' => $title,
                    ],
                    'hero_image' => [
                        'type' => 'image',
                        'image_url' => $temadag['image'],
                        'alt_text' => $title,
                    ],
                    'subtitle' => [
                        'type' => 'mrkdwn',
                        'text' => slackSubtitle($temadag['links']),
                    ],
                    'body' => [
                        'type' => 'mrkdwn',
                        // the card title already shows the emoji
                        'text' => "$when är det {$temadag['title']}"
                            . ($isToday ? '!' : '.'),
                    ],
                ],
                [
                    'type' => 'context',
                    'elements' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => $footer,
                        ],
                    ],
                ],
            ],
        ];

        if ($isDebug) {
            // debug previews the payload, so it never posts, whatever
            // WEBHOOK_URL says
            echo "```json\n" . json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ) . "\n```\n\n";
            echo "Not posting to Slack in debug mode.\n";
        } elseif ($webhookURL) {
            // Guzzle throws on 4xx/5xx statuses, refused connections, timeouts
            // and unencodable payloads, and a malformed URL throws
            // InvalidArgumentException; an unfollowed 3xx means nothing was
            // delivered either
            try {
                $posted = (new Client())
                    ->post($webhookURL, slackPostOptions($payload))
                    ->getStatusCode() < 300;
            } catch (GuzzleException|InvalidArgumentException) {
                $posted = false;
            }

            // the remaining announcements and reminders still run
            if (!$posted) {
                echo "Posting to Slack failed.\n";
                $failed = true;
            }
        } else {
            echo "No webhook URL found.\n";
        }
        echo "------------------------------------------------------\n";
        echo "\n\n";
    }
}

exit($failed ? 1 : 0);
