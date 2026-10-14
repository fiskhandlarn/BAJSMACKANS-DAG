<?php

declare(strict_types=1);

// Guzzle request options for posting a payload to a Slack webhook as JSON.
function slackPostOptions(array $payload): array
{
    return [
        'json' => $payload,
        // curl never followed redirects; following one would re-send the payload to wherever the response points
        'allow_redirects' => false,
        // timeouts stop a hung Slack from blocking the run
        'connect_timeout' => 10,
        'timeout' => 30,
    ];
}
