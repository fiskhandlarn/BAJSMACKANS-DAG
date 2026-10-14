<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

require_once __DIR__ . '/../../src/slack.php';

class SlackPostOptionsTest extends Unit
{
    // A Slack that accepts the connection but never answers must not block the run until the job limit.
    public function testTimeouts(): void
    {
        $options = slackPostOptions('{}');

        $this->assertSame(10, $options['connect_timeout']);
        $this->assertSame(30, $options['timeout']);
    }
}
