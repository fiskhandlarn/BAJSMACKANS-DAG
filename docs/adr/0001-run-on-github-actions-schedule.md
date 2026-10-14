# Run on a GitHub Actions schedule instead of a server

The app is a CLI script (`php index.php`) that posts to a Slack webhook once a day at 08:00 Europe/Stockholm. It used to run from a crontab on a developer's machine, with files copied over by hand. We now run it from a scheduled GitHub Actions workflow that checks out `main`. That way every merge to main is live with no deploy step. The script serves no requests and keeps no state, so a server and atomic deploys would add upkeep without solving anything.

## Considered Options

- **Server + Deployer** (`releases/` + `current` symlink, shared `.env`): atomic releases protect a site that is serving requests while it deploys. A cron script that runs once a day has none, so we rejected it.
- **Server + rsync or `git pull` on merge**: works, but we would have to maintain a host, SSH keys and a crontab for a single daily job.

## Consequences

- The workflow has one trigger at 07:45 with `timezone: Europe/Stockholm`, so GitHub handles summer and winter time. It started as two UTC triggers with a home-made gate; `timezone` replaced that (#13).
- Runs can start late, and GitHub can drop them, most often at the full hour. 07:45 avoids the full hour; with no delay, the post arrives at 07:45 instead of 08:00.
- `WEBHOOK_URL` is a repo secret, passed to the step as an env var. The runner's PHP has no `E` in `variables_order`, so env vars never reach `$_ENV`, and immutable phpdotenv will not load `.env` over a var that is already set. `env()` in `src/env.php` therefore falls back to `getenv()`.
- On a public repo, scheduled workflows turn off after 60 days with no repo activity. GitHub emails a warning first, and one click turns the workflow back on.
- The old crontab must be removed when this goes live, or Slack gets two posts each day.
