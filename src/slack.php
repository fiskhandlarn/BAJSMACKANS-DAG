<?php

declare(strict_types=1);

// Guzzle request options for posting a JSON payload to a Slack webhook.
function slackPostOptions(string $json): array
{
    return [
        // body, not Guzzle's json option, which would re-encode the payload with Unicode and slashes escaped
        'body' => $json,
        'headers' => ['Content-Type' => 'application/json'],
        // timeouts stop a hung Slack from blocking the run
        'connect_timeout' => 10,
        'timeout' => 30,
    ];
}
