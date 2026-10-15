<?php

declare(strict_types=1);

// Prints a temadag's Slack payload and a Block Kit Builder link to it.
// Usage, from the repo root:
// php .claude/skills/add-temadag/preview.php YYYY-MM-DD "Title"
// The link needs SLACK_TEAM_ID (the T… id in your Block Kit Builder URL) in
// .env, which stays out of git.

require 'src/env.php';

[, $date, $title] = $argv + [null, null, null];
if (!$date || !$title) {
    fwrite(STDERR, "Usage: php preview.php YYYY-MM-DD \"Title\"\n");
    exit(1);
}

exec('php index.php --debug --date=' . escapeshellarg($date), $output);
preg_match_all('/```json\n(.*?)\n```/s', implode("\n", $output), $matches);

foreach ($matches[1] as $json) {
    $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    if ($payload['blocks'][0]['title']['text'] !== $title) {
        continue;
    }

    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    echo json_encode($payload, $flags | JSON_PRETTY_PRINT), "\n\n";

    // the builder reads the payload from the fragment, encoded like JS's
    // encodeURI: rawurlencode, then put back what encodeURI leaves alone
    $kept = str_split(":/,;?@&=+$#!'()*");
    $fragment = strtr(
        rawurlencode(json_encode($payload, $flags)),
        array_combine(array_map('rawurlencode', $kept), $kept),
    );
    $team = env('SLACK_TEAM_ID');
    echo $team
        ? "https://app.slack.com/block-kit-builder/$team/builder#$fragment\n"
        : "Set SLACK_TEAM_ID in .env for a Block Kit Builder link.\n";
    exit(0);
}

fwrite(STDERR, "No card titled \"$title\" on $date.\n");
exit(1);
