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
1. Create a fine-grained personal access token for this repo only, with the repository permission Actions: read and write. Note its expiry date.
2. On the server, `crontab -e` and add a `GH_TOKEN=<token>` line, then a line that runs at `0 8 * * *` and calls `curl -fsS -X POST` against `https://api.github.com/repos/fiskhandlarn/BAJSMACKANS-DAG/actions/workflows/daily.yml/dispatches` with the headers `Authorization: Bearer $GH_TOKEN` and `Accept: application/vnd.github+json` and the body `{"ref":"main"}`. With `-f`, an HTTP error makes curl exit non-zero, and cron mails its output when the server has mail set up.
3. Run the curl line once by hand and check that a `workflow_dispatch` run of Daily appears.
4. Renew the token before it expires.

## Verification
- `daily.yml` has no `schedule` key, and the Actions tab lists no Schedule probe workflow.
- The manual curl starts a Daily run within seconds.
- The next morning, a Daily run starts at about 08:00 Stockholm time, started by `workflow_dispatch`.
