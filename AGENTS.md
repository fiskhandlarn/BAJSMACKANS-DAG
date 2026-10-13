# Agent instructions

## Plans

Save every implementation plan to `docs/plans/YYYY-MM-DD-<slug>.md`, dated (ISO 8601) the day the plan was written, e.g. `docs/plans/2026-10-13-run-on-github-actions-schedule.md`.

Keep plans short prose: context, change, manual steps, verification. Name files by path instead of quoting code; the code is documented where it is implemented. Plans hold no code blocks.

When a commit changes something the plan describes, update the plan in that same commit.

## Workflows

The repo is public and `.github/workflows/daily.yml` uses the `WEBHOOK_URL` secret. Trigger PR workflows with `pull_request`, which gives fork PRs no secrets. Never use `pull_request_target`: it runs with the repo's secrets, and checking out the PR's code in it hands those secrets to any fork.
