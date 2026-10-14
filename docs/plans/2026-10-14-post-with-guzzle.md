# Post to Slack with Guzzle

## Context
Issue #9. The daily run posted each announcement and reminder by building a `curl` command line and running it with `exec()`, quoting the payload with `escapeshellarg`. The only failure signal was curl's exit code, and the tests faked curl with a shell script on `PATH`.

## Change
`guzzlehttp/guzzle` is in `require`. `index.php` POSTs each announcement and reminder to `WEBHOOK_URL` with a Guzzle client. Any Guzzle exception (HTTP error status, refused connection, timeout) prints `Posting to Slack failed.`, the remaining announcements and reminders still run, and the run exits 1.

`slackPostOptions()` in `src/slack.php` builds the request options: the payload as `body` with a JSON content type, a 10 s connect timeout and a 30 s total timeout. The payload goes in `body`, not Guzzle's `json` option, because that option re-encodes it with Unicode and slashes escaped; `body` sends exactly what `--debug` prints.

`--debug` and the missing-webhook path are unchanged.

Tests: `tests/Unit/SlackPostOptionsTest.php` checks the timeouts. `card` checks the received body keeps Unicode and slashes unescaped. The payload tests in `tests/Cli/DaysCest.php` post to a `php -S` server running `tests/Support/save-request.php`, which saves the request body to `tests/_output/`. `httpErrorPost` shares the same server helper. The fake curl in `tests/Support/bin/curl` is gone.

## Manual steps
None.

## Verification
- `composer test` and `composer lint:php` pass.
- `index.php` has no `exec(` and no `curl` call.
- The next daily post with a match reaches Slack.
