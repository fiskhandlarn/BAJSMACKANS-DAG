# Post to Slack with Guzzle

## Context
Issue #9. The daily run posted each announcement and reminder by building a `curl` command line and running it with `exec()`, quoting the payload with `escapeshellarg`. The only failure signal was curl's exit code, and the tests faked curl with a shell script on `PATH`.

## Change
`guzzlehttp/guzzle` and `ext-curl` are in `require`. Guzzle applies the connect timeout only through its curl handler; without `ext-curl` it falls back to PHP streams, which ignore it. `index.php` POSTs each announcement and reminder to `WEBHOOK_URL` with a Guzzle client. A redirect, an HTTP error status, a refused connection, a timeout, an unencodable payload or a malformed `WEBHOOK_URL` prints `Posting to Slack failed.`, the remaining announcements and reminders still run, and the run exits 1.

`slackPostOptions()` in `src/slack.php` builds the request options: the payload in Guzzle's `json` option, a 10 s connect timeout, a 30 s total timeout, and redirects off. curl never followed redirects, and following one would re-send the payload to wherever the response points. Guzzle encodes it with Unicode and slashes escaped, so the bytes differ from what `--debug` prints, but Slack decodes the same message.

`--debug` and the missing-webhook path are unchanged.

Tests: `tests/Unit/SlackPostOptionsTest.php` checks the timeouts and that redirects are off. `redirectPost` posts to `tests/Support/http-302.php` and `malformedWebhookURL` posts to `http://:80`, and both expect the run to fail. The payload tests in `tests/Cli/DaysCest.php` post to a `php -S` server running `tests/Support/save-request.php`, which saves the request body to `tests/_output/` only when it has the JSON content type. `httpErrorPost` shares the same server helper. The fake curl in `tests/Support/bin/curl` is gone.

## Manual steps
None.

## Verification
- `composer test` and `composer lint:php` pass.
- `index.php` has no `exec(` and no `curl` call.
- The next daily post with a match reaches Slack.
