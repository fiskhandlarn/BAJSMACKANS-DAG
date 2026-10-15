# Add-temadag skill

## Context
Adding a temadag takes the same research every time: a second source for the day rule, links, an image, a title and an emoji, then a preview of the Slack card. Doing it by hand for the five temadagar from recept.se's fikakalender, and for Äpplets dag as a test run, showed which steps are easy to get wrong: a date printed for one year that is really a movable rule, list pages that misreport a date, link sets longer than Slack's 150-character subtitle.

## Change
`.claude/skills/add-temadag/SKILL.md` is a typed-only skill (`/add-temadag`) holding those steps. The user approves every title, link set and image after seeing a preview, and the list a temadag was found on goes into `## Sources` in `README.md`. The skill also lists sites to search for new temadagar.

`.claude/skills/add-temadag/preview.php` runs `index.php --debug` for a date, picks the card with the given title, and prints its JSON and a Block Kit Builder link. The link needs the Slack workspace id, read from `SLACK_TEAM_ID` in `.env` so the id stays out of the public repo; `.env.example` lists the key empty. Without it the script prints the JSON only.

## Manual steps
Set `SLACK_TEAM_ID` in `.env` to the `T…` id from your Block Kit Builder URL.

## Verification
- `php .claude/skills/add-temadag/preview.php 2027-02-15 "Geléhallonens dag"` prints the card and a link that opens it in Block Kit Builder.
- With `SLACK_TEAM_ID` unset, it prints the card and a hint instead of the link; an unknown title exits 1.
- `skillPreview` in `tests/Cli/DaysCest.php` runs the script against `tests/Support/Data/quotes.json` and checks that the link decodes back to the printed card, so CI catches a change to the `--debug` output that breaks the script. Its fixture link is percent-encoded, and the test fails if the link carries `%25`: Block Kit Builder percent-decodes the payload a second time, so the script sends each `%` as the JSON escape `\u0025`.
- `composer run lint:style` passes.
