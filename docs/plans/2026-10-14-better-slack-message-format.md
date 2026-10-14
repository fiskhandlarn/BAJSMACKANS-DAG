# Better formatting in Slack messages

## Context
Issue #12. A post puts the day's link after ` - ` on the same line as the day, and says nothing about which app sent it or which version. The repo has no version. Release tags were considered, but nothing reads the version except a person in Slack, so the short commit SHA is enough: no tags, no release workflow, no extra permissions.

## Change
`index.php` builds the message:
- The link goes on its own line under the day.
- A Slack `context` block under the image links to the GitHub repo and shows the short SHA of the running commit, linked to that commit.
- The SHA comes from `git rev-parse --short HEAD`. The daily workflow's checkout includes `.git`, so this works on the runner. Without git or `.git`, the version reads `dev` and has no link.

`--debug` prints the payload and never posts, even with `WEBHOOK_URL` set, so a preview can't reach Slack by mistake.

`tests/Cli/DaysCest.php` checks the posted payload through the fake curl in `tests/Support/bin/curl`: the link line, the repo link, the SHA, the `dev` fallback and that `--debug` doesn't post.

## Manual steps
None.

## Verification
- `composer run test` passes.
- `php index.php --debug --date=<a day in days.json>` prints the payload with the link on its own line and the context block.
- The next daily post with a match shows the new layout and the SHA of `main`.
