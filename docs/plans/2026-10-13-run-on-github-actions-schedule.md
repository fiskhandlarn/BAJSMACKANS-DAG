# Move BAJSMACKANS-DAG to GitHub Actions cron

> Implemented with one change: the gate step and `setup-php` were later merged into one `run:` step. See `.github/workflows/daily.yml` for the current version.

## Context
App is a CLI script (`php index.php`) run daily 08:00 by cron on a dev's machine, files copied by hand. It posts to a Slack webhook (`WEBHOOK_URL` from `.env`, read via `src/env.php` `env()` → `$_ENV`). No web traffic, no state. Goal: merge to main = live, no manual copying.

Chosen: no server. A scheduled GitHub Actions workflow checks out `main` and runs the script. Always runs main HEAD, so "deploy" disappears. Deployer/atomic deploys skipped: nothing serves requests mid-deploy.

## Change
New file `.github/workflows/daily.yml`:

```yaml
name: Daily

on:
  schedule:
    - cron: '0 6 * * *' # 08:00 CEST (summer)
    - cron: '0 7 * * *' # 08:00 CET (winter)
  workflow_dispatch:

jobs:
  notify:
    runs-on: ubuntu-latest
    steps:
      # cron is UTC; only the trigger matching Stockholm's current offset runs
      - id: dst
        run: |
          zone=$(TZ=Europe/Stockholm date +%Z)
          if [ "${{ github.event_name }}" = workflow_dispatch ] \
            || { [ "${{ github.event.schedule }}" = '0 6 * * *' ] && [ "$zone" = CEST ]; } \
            || { [ "${{ github.event.schedule }}" = '0 7 * * *' ] && [ "$zone" = CET ]; }; then
            echo run=true >> "$GITHUB_OUTPUT"
          fi
      - if: steps.dst.outputs.run
        uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1
      - if: steps.dst.outputs.run
        uses: shivammathur/setup-php@eb7c497e18156a6bbabfef1d3a82760b9eda3962 # 2.40.0
        with:
          php-version: '8.1'
          coverage: none
      - if: steps.dst.outputs.run
        run: printf 'WEBHOOK_URL=%s\nDATA_JSON=days.json\n' "$WEBHOOK_URL" > .env
        env:
          WEBHOOK_URL: ${{ secrets.WEBHOOK_URL }}
      - if: steps.dst.outputs.run
        run: composer install --no-dev --no-interaction --no-progress
      - if: steps.dst.outputs.run
        run: php index.php
```

Notes:
- Write `.env` instead of passing an env var: `env()` reads `$_ENV`, and immutable phpdotenv skips vars already in `$_SERVER`, so a plain env var may never reach `$_ENV` (depends on `variables_order`). `post-install-cmd` only copies `.env.example` when `.env` is missing, so ours survives.
- First run must not print "No webhook URL found.".
- Pin action SHAs same as `pull-request.yml`.
- Small commit: `ci: run daily notifier on GitHub Actions schedule`.

## Manual steps (user)
1. Add repo secret `WEBHOOK_URL` (Settings → Secrets → Actions).
2. Merge, then run once via "Run workflow" (workflow_dispatch) to confirm.
3. Tell the other dev to remove their crontab entry the day it goes live, or Slack gets double posts.

## Known limits
- GitHub schedule can start 5–30+ min late, worst at top of hour. Fine for a fika reminder.
- Public repo: scheduled workflows auto-disable after 60 days without repo activity. GitHub emails a warning; re-enable with one click. Add keepalive only if it bites.
- If GitHub's `schedule` gains a timezone option, drop the DST gate.

## Verification
- `workflow_dispatch` run on main: log shows "Checking days against …" and, on a match day, a Slack post.
- Check next two scheduled runs: one job does the work, the other skips all steps.
