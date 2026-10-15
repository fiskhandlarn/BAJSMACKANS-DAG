# Several links per temadag

## Context
Issue #28. A temadag has one `link` string, sent unchanged as the card's subtitle. A temadag with two sources, such as Wikipedia plus vilkendag.nu, would need hand-written Slack link syntax in `days.json`.

## Change
- A temadag in `days.json` and the test fixtures in `tests/Support/Data/` has `links`, a non-empty array of URLs, in place of `link`. Every existing temadag becomes a one-item array. The fixtures use real URLs, since a host can't be parsed from a placeholder.
- `slackSubtitle()` in `src/slack.php` builds the subtitle: each URL becomes `<url|host>`, joined with ` · `. The host is the URL's host as parsed, `www.` included. `index.php` sends it as the card's subtitle.
- Slack rejects the whole message when the subtitle is over 150 characters. The app never trims it: the `fields` test in `tests/Cli/DaysCest.php` checks the built subtitle, so a pull request with too many or too long links fails CI. The same test fails a missing, empty or non-array `links`, or a URL in it without a host.
- `CONTEXT.md` says a temadag has one or more links.

URLs are not escaped for Slack mrkdwn, so the `fields` test also fails a URL containing `&`, `<` or `>`.

## Manual steps
None.

## Verification
- `composer run test` and `composer run lint:style` pass.
- `php index.php --debug --date=<a day in days.json>` prints a subtitle like `<https://sv.wikipedia.org/wiki/Marsipan|sv.wikipedia.org>`.
