# Move BAJSMACKANS-DAG to GitHub Actions cron

## Context
The app is a CLI script that posts to a Slack webhook once a day at 08:00 Stockholm time. It ran from a crontab on a developer's machine, and files were copied over by hand. Goal: every merge to main goes live with no manual step. Decision and rejected options: `docs/adr/0001-run-on-github-actions-schedule.md`.

## Change
`.github/workflows/daily.yml` runs the script on a schedule from `main`:
- Two UTC cron triggers (06:00 and 07:00). A check on Stockholm's current offset (CEST/CET) lets only the 08:00-local one do work. A manual run always works.
- The `WEBHOOK_URL` repo secret reaches the script as an env var. `env()` in `src/env.php` falls back to `getenv()`, because the runner's PHP leaves env vars out of `$_ENV`.
- The run fails when `WEBHOOK_URL` is empty, so a missing secret shows up on every run, not only on days with a match.
- A failed Slack post (refused connection, HTTP error, or timeout after 30 s) makes `index.php` exit 1, so the run fails. Tracked in #6.
- The job times out after 5 minutes instead of the 6 h default.
- Uses the runner's preinstalled PHP and composer. The PR `test` job keeps `setup-php` 8.1 with coverage off.

## Manual steps
1. Add the repo secret `WEBHOOK_URL`.
2. Merge, then run the workflow once by hand.
3. Remove the old crontab the same day, or Slack gets two posts.

## Verification
- Manual run: the log shows "Checking days against …" and not "No webhook URL found.".
- A run with a bad webhook URL fails and logs "Posting to Slack failed.".
- Next two scheduled runs: one does the work, the other exits early.
