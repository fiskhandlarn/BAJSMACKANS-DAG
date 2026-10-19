# Claude hooks and upkeep skills

## Context
Three rules in `AGENTS.md` rested on the agent remembering them: commit on a branch, never use `pull_request_target`, run `composer run fix:style` before committing. Two things also had no upkeep. The links and images in the calendar rot after `add-temadag` checks them, and the SHA-pinned actions in `.github/workflows/` never move.

## Change
`.claude/settings.json` gains hooks next to the `composer.lock` guard. Hook commands run under `/bin/sh`, so they stay POSIX.
- One blocks any Bash command naming `git` and then `commit` or `push` while `main` is checked out in the command's working directory. It errs toward blocking: `git checkout -b x && git commit` on `main` is blocked too, so the branch comes first, on its own.
- One blocks `pull_request_target` from reaching `.github/workflows/`: through Edit, Write and MultiEdit content, and through any Bash command naming both. These are guardrails against slips, not a wall.
- One runs php-cs-fixer on every PHP file Claude edits, limited to the files its config covers.

`/audit-calendar` (`.claude/skills/audit-calendar/`) runs `audit.sh`. The script lists every link, image and emoji breaking `add-temadag`'s Links, Image and Emoji rules. The skill then walks the user through fixes by those rules, one temadag at a time. The script sends a descriptive User-Agent, since Wikimedia answers curl's default one with 429, and retries transient failures, judging only the final response. An unreadable calendar, or one whose temadagar lack a title, links or image, stops it with exit 2. Servers that ignore its one-byte range request and send no length are measured by what they sent, and a host that does not answer at all is reported as unreachable. Every problem sets the exit code, including a temadag whose emoji is undecided (see `docs/plans/2026-10-19-emoji-property.md`).

`.github/dependabot.yml` opens weekly PRs bumping the actions, and closes its own once `main` has the version. `/bump-actions` (`.claude/skills/bump-actions/`) bumps them all at once on demand, resolving each release tag to its commit SHA.

`.github/dependabot.yml` also opens one weekly PR bumping every composer package. Dependabot runs composer itself, so the lock changes only through composer and resolution respects `config.platform.php`. Its `increase` strategy raises the constraints in `composer.json` too. `/bump-composer` stays for major bumps and its blocker report, which Dependabot does not give.

## Manual steps
None. Dependabot starts once the config is on `main`.

## Verification
- Each hook command, fed a sample payload through `sh -c`: the `main` guard exits 2 on `main` for `git commit`, `git -C . commit`, `FOO=1 git commit`, `(git commit)`, `sudo git push` and `git` and `commit` split across lines, and 0 for `git status` or on a branch. The workflow guard exits 2 for `pull_request_target` sent to a workflow by Edit, MultiEdit or `sed -i`, and 0 elsewhere. The style hook reformats a PHP file whose path has a space and leaves `vendor/` alone.
- `bash .claude/skills/audit-calendar/audit.sh` on the calendar at 1df2b2f exits 1 and lists the broken Wikimedia thumbnails, oversized images and emoji-less titles, with no 429s.
- `composer run lint:style` passes.
