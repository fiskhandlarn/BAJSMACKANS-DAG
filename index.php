<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/env.php';
require_once __DIR__ . '/src/days.php';

use Carbon\Carbon;

error_reporting(E_ALL & ~E_DEPRECATED);

date_default_timezone_set(TIMEZONE);

$calendarJSON = @file_get_contents(__DIR__ . '/' . env('DATA_JSON'));

if (!$calendarJSON) {
  die(env('DATA_JSON') . " not found.\n");
}

$calendar = @json_decode($calendarJSON, true);

if (!$calendar) {
  die("Invalid JSON.\n");
}

$now = new Carbon('now', TIMEZONE);
$isDebug = false;

if (php_sapi_name() == "cli") {
  global $argv;

  foreach ( $argv as $argument ) {
    if( $argument != $argv[ 0 ] ) {
      if ('--debug' === $argument) {
        $isDebug = true;
      }

      $pair = explode( "=", $argument );
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
// the short SHA of the running commit is the app's version; no git or .git gives 'dev'
// git searches parent directories, so without its own .git (a file in a worktree) a copy inside another repo would report that repo's SHA
$version = file_exists(__DIR__ . '/.git') ? trim((string) shell_exec('git -C ' . escapeshellarg(__DIR__) . ' rev-parse --short HEAD 2>/dev/null')) : '';
$versionText = $version ? "<$repoURL/commit/$version|$version>" : 'dev';

echo "Checking calendar against " . $now->toDateString() . " and " . $tomorrow->toDateString() . ":\n\n";

foreach($calendar as $idx => $temadag) {
  // resolve against each date's own year so --date and new year's eve work; unparseable day rules resolve to null and never match
  $isToday = $now->format("Y-m-d") == resolveDayRule($temadag["day"], $now->year)?->format("Y-m-d");
  $isTomorrow = $tomorrow->format("Y-m-d") == resolveDayRule($temadag["day"], $tomorrow->year)?->format("Y-m-d");

  if($isToday || $isTomorrow) {
    echo "------------------------------------------------------\n";
    echo "🎉 " . $temadag['title'] . ' is ' . ($isToday ? 'today' : 'tomorrow') . "! 🎉\n\n";
    $json = json_encode([
      'blocks' => [
        [
          'type' => 'card',
          'title' => [
            'type' => 'mrkdwn',
            'text' => $temadag['title'],
          ],
          'hero_image' => [
            'type' => 'image',
            'image_url' => $temadag['image'],
            'alt_text' => $temadag['title'],
          ],
          'subtitle' => [
            'type' => 'mrkdwn',
            'text' => $temadag['link'],
          ],
          'body' => [
            'type' => 'mrkdwn',
            'text' => sprintf('%s är det %s', ($isToday ? ':warning: Idag' : ':spiral_calendar_pad: Imorgon'), $temadag['title']),
          ],
        ],
        [
          'type' => 'context',
          'elements' => [
            [
              'type' => 'mrkdwn',
              'text' => "<$repoURL|BAJSMACKANS-DAG> · $versionText",
            ],
          ],
        ],
      ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    if ($isDebug) {
      // debug previews the payload, so it never posts, whatever WEBHOOK_URL says
      echo "```json\n" . $json . "\n```\n\n";
      echo "Not posting to Slack in debug mode.\n";
    } elseif ($webhookURL) {
      // --fail turns HTTP errors into a non-zero exit code; timeouts stop a hung Slack from blocking the run
      $exec = "curl --fail --silent --show-error --connect-timeout 10 --max-time 30 -X POST -H 'Content-type: application/json' --data " . escapeshellarg($json) . " " . escapeshellarg($webhookURL);
      exec($exec, result_code: $resultCode);

      if (0 !== $resultCode) {
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
