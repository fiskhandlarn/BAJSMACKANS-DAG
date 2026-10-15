# Start the daily run from a server crontab

Supersedes the trigger in ADR 0001 and the consequences that follow from it. The checkout of `main` and the `WEBHOOK_URL` handling in 0001 still hold.

GitHub's `schedule` trigger never started a daily run in this repo, and since about 2026-08-26 it starts runs hours late or not at all for many accounts, on any minute (community discussion 206019). `workflow_dispatch` is unaffected. So a crontab on a server we already run POSTs to the workflow's dispatch API at 08:00 Europe/Stockholm, and the workflow has no `schedule` trigger.

## Considered Options

- **Keep `schedule` next to the crontab**, so runs resume on their own once GitHub fixes it: a late scheduled run would post the day's announcements and reminders twice, hours apart. The app keeps no state to notice that, and a `concurrency` group only stops overlapping runs. Rejected.
- **Server crontab runs `php index.php` directly**: needs a deploy on every merge, which ADR 0001 rejected.
- **A hosted cron service** (cron-job.org and the like): no server to keep, but a third party holds a token that can start our workflows, and we already have a server. Rejected.

## Consequences

- The run starts at 08:00 again, not 07:45: dispatched runs start within seconds.
- The token is a fine-grained personal access token limited to this repo and Actions: read and write. It has no expiration: an expired token would silently stop the daily run, while a leaked one can only start, cancel or delete runs of this repo, not read secrets or push code.
- A server that is down means a dropped run, with no alert. A dead-man's switch (#15) would catch it. The fallback trigger (#14) no longer applies.
- The 60-day inactivity rule in ADR 0001 only turns off `schedule` triggers, so it no longer applies.
