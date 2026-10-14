# Better formatting in Slack messages

## Context
Issue #12. A post puts the day's link after ` - ` on the same line as the day, and says nothing about which app sent it or which version. The repo has no version. Release tags were considered, but nothing reads the version except a person in Slack, so the short commit SHA is enough: no tags, no release workflow, no extra permissions.

## Change
`index.php` builds the message:
- The day is a Slack `card` block: the title as title, the image as hero image, the link as subtitle (its own line), and "Idag/Imorgon är det …" as body. Every link and title in `days.json` fits the card's limits (150 characters for title and subtitle, 200 for body).
- A Slack `context` block under the card links to the GitHub repo and shows the short SHA of the running commit, linked to that commit.
- The SHA comes from `git rev-parse --short HEAD`. The daily workflow's checkout includes `.git`, so this works on the runner. Without git or the app's own `.git`, the version reads `dev` and has no link. The `.git` check stops a copy inside another repo from reporting that repo's SHA, since git searches parent directories.

`--debug` prints the payload and never posts, even with `WEBHOOK_URL` set, so a preview can't reach Slack by mistake.

`tests/Cli/DaysCest.php` checks the posted payload through the fake curl in `tests/Support/bin/curl`: the card fields, the repo link, the SHA, the `dev` fallback (also for a copy inside another repo) and that `--debug` doesn't post.

## Manual steps
None.

## Verification
- `composer run test` passes.
- `php index.php --debug --date=<a day in days.json>` prints the payload with the card and the context block.
- The next daily post with a match shows the new layout and the SHA of `main`.
