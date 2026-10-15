# Start the daily run from a server crontab

## Context
GitHub never started a single scheduled daily run: the `schedule` slots on 2026-10-14 (06:00 and 07:00 UTC) and 2026-10-15 (07:45 Stockholm) produced no run at all, and neither did a 15-minute probe workflow without `timezone:`. Since about 2026-08-26 GitHub's scheduler starts runs hours late or never, across many accounts and on off-peak minutes too (community discussion 206019). `workflow_dispatch` runs start within seconds. Decision: `docs/adr/0002-start-daily-run-from-server-crontab.md`.

## Change
- `.github/workflows/daily.yml` keeps only `workflow_dispatch`. The `schedule` trigger is gone, so a late scheduled run can never post the day's announcements and reminders a second time.
- A crontab on the server POSTs to the workflow's dispatch API at 08:00. The server runs in `Europe/Stockholm`, so cron handles summer and winter time. The workflow still checks out `main`, so every merge is live with no deploy step.
- `.github/workflows/schedule-probe.yml` is deleted.
- `docs/plans/2026-10-13-run-on-github-actions-schedule.md` and ADR 0001 point here for the trigger.
- #14 (fallback trigger) no longer applies. #15 (dead-man's switch) stays open: it is the only alert if the server is down.

## Manual steps
1. Create a fine-grained personal access token for this repo only, with the repository permission Actions: read and write and no expiration.
2. On the server, `crontab -e`, paste the lines from `crontab` in the repo root and replace `<token>`. The `curl` timeouts end a stalled request instead of letting it run into the next morning. With `-f`, an HTTP error makes curl exit non-zero, and cron mails its output when the server has mail set up.
3. Set `GH_TOKEN` in the shell, then run the curl line by hand on a day the daily run has not posted, e.g. the day of setup after 08:00, so Slack does not get the same announcements and reminders twice. Check that a `workflow_dispatch` run of Daily appears.

## Verification
- The next morning, a Daily run starts at about 08:00 Stockholm time, started by `workflow_dispatch`.
