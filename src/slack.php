<?php

declare(strict_types=1);

// Guzzle request options for posting a payload to a Slack webhook as JSON.
function slackPostOptions(array $payload): array
{
    return [
        'json' => $payload,
        // curl never followed redirects; following one would re-send the
        // payload to wherever the response points
        'allow_redirects' => false,
        // timeouts stop a hung Slack from blocking the run
        'connect_timeout' => 10,
        'timeout' => 30,
    ];
}

// A temadag's title as Slack shows it: with its emoji, when it has one.
// emoji false means decided: no emoji; a missing emoji is undecided.
function displayTitle(array $temadag): string
{
    $emoji = $temadag['emoji'] ?? false;

    return $temadag['title'] . (false === $emoji ? '' : " $emoji");
}

// A card subtitle linking each URL, labelled with its host.
function slackSubtitle(array $links): string
{
    return implode(' · ', array_map(
        fn(string $url) => "<$url|" . parse_url($url, PHP_URL_HOST) . '>',
        $links,
    ));
}
