# Agent instructions

## Plans

Save every implementation plan to `docs/plans/YYYY-MM-DD-<slug>.md`, dated (ISO 8601) the day the plan was written, e.g. `docs/plans/2026-10-13-run-on-github-actions-schedule.md`.

Keep plans short prose: context, change, manual steps, verification. Name files by path instead of quoting code; the code is documented where it is implemented. Plans hold no code blocks.

When a commit changes something the plan describes, update the plan in that same commit.

## Branches

Never commit directly to `main`: it has branch protection. Commit on a branch and merge through a pull request.

## Workflows

The repo is public and `.github/workflows/daily.yml` uses the `WEBHOOK_URL` secret. Trigger PR workflows with `pull_request`, which gives fork PRs no secrets. Never use `pull_request_target`: it runs with the repo's secrets, and checking out the PR's code in it hands those secrets to any fork.

## Dependencies

Change `composer.lock` only through composer (`composer require`, `composer update`), never by hand.

Write `composer.json` constraints as caret ranges with the full patch version, `^n.n.n` (e.g. `^3.14.2`, not `^3.14`). `composer require vendor/package:^n.n.n` (with `--dev` for dev packages) does both.

`config.platform.php` in `composer.json` is the minimum PHP version, so composer resolves for it on any machine. Raise it together with `require.php`.

## Agent skills

### Issue tracker

Issues live in this repo's GitHub Issues, used through the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

The five default triage labels, each label string equal to its role name. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: one `CONTEXT.md` and `docs/adr/` at the repo root. See `docs/agents/domain.md`.
