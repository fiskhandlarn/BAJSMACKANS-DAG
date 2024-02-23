<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/env.php';

use Carbon\Carbon;

error_reporting(E_ALL & ~E_DEPRECATED);

$daysJSON = @file_get_contents(__DIR__ . '/' . env('DATA_JSON'));

if (!$daysJSON) {
  die(env('DATA_JSON') . " not found.\n");
}

$days = @json_decode($daysJSON, true);

if (!$days) {
  die("Invalid JSON.\n");
}

foreach($days as $key => $value) {
  $day = $days[$key]['day'];

  if (false !== strpos($day, 'before')) {
    if (preg_match('#(\d+ days) before (\w+\b)#', $day, $matches)) {
      $carbon = Carbon::createFromTimestamp(call_user_func($matches[2]))->sub(\DateInterval::createFromDateString($matches[1]));
    } else {
      // skip this day
      unset($days[$key]);
      continue;
    }
  } else {
    $carbon = new Carbon($day);
  }

  $days[$key]['day'] = $carbon;
}

$now = new Carbon('now');
$tomorrow = new Carbon('tomorrow');
$webhookURL = env('WEBHOOK_URL');

foreach($days as $idx => $daydata) {
  $isToday = $now->format("Y-m-d") == $daydata["day"]->format("Y-m-d");
  $isTomorrow = $tomorrow->format("Y-m-d") == $daydata["day"]->format("Y-m-d");

  if($isToday || $isTomorrow) {
    $json = <<<EOT
{
        "blocks": [
                {
                        "type": "section",
                        "text": {
                                "type": "mrkdwn",
                                "text": "%s är det *%s* - %s"
                        }
                },
                {
                        "type": "image",
                        "image_url": "%s",
                        "alt_text": "%s"
                }
        ]
}
EOT;
    $json = sprintf($json, ($isToday ? ':warning: Idag' : ':spiral_calendar_pad: Imorgon'), $daydata["title"], $daydata["link"], $daydata["image"], $daydata["title"]);
    $exec = "curl -X POST -H 'Content-type: application/json' --data '" . $json . "' " . $webhookURL;
    exec($exec);
  }
}
