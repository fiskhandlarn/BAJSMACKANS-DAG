<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

require_once __DIR__ . '/../../src/slack.php';

class SlackSubtitleTest extends Unit
{
    // The label is the URL's host, unchanged.
    public function testOneLink(): void
    {
        $url = 'https://sv.wikipedia.org/wiki/Gel%C3%A9hallon';

        $this->assertSame("<$url|sv.wikipedia.org>", slackSubtitle([$url]));
    }

    public function testSeveralLinks(): void
    {
        $this->assertSame(
            '<https://sv.wikipedia.org/wiki/Marsipan|sv.wikipedia.org>'
                . ' · <https://www.vilkendag.nu/x|www.vilkendag.nu>',
            slackSubtitle([
                'https://sv.wikipedia.org/wiki/Marsipan',
                'https://www.vilkendag.nu/x',
            ]),
        );
    }
}
