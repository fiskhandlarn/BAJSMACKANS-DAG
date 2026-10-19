<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

require_once __DIR__ . '/../../src/slack.php';

class DisplayTitleTest extends Unit
{
    public function testEmoji(): void
    {
        $this->assertSame(
            'Fettisdagen :semla:',
            displayTitle(['title' => 'Fettisdagen', 'emoji' => ':semla:']),
        );
    }

    public function testNoEmoji(): void
    {
        $this->assertSame(
            'Fettisdagen',
            displayTitle(['title' => 'Fettisdagen', 'emoji' => false]),
        );
        $this->assertSame(
            'Fettisdagen',
            displayTitle(['title' => 'Fettisdagen']),
        );
    }
}
