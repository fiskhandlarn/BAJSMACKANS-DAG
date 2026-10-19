# Emoji property

## Context
A temadag's Slack emoji was the tail of its title, so nothing told "not decided yet" from "decided: no emoji". `/audit-calendar` flagged every title without an emoji on every run, and kept titles out of its exit code as a workaround. Issue #44.

## Change
Each temadag in `days.json` may carry an `emoji` property: a Slack `:name:` string, or `false` for "decided: no emoji". A missing property means undecided. `null` is not allowed, since PHP's `??` and `isset` read it as missing. Titles no longer hold the emoji; the 49 existing emoji moved to `emoji` unchanged, and the 20 temadagar without one stay undecided.

`displayTitle()` in `src/slack.php` builds the title as shown: the title, then a space and the emoji when there is one. `index.php` uses it for the card title, the image's alt text, the body and the console line, and the tests use it too.

The `fields` test in `tests/Cli/DaysCest.php` rejects an `emoji` that is neither `false` nor a `:name:` string, a title ending in an emoji, and a shown title over 150 characters. `audit.sh` reports temadagar with no `emoji` property, and they set its exit code like links and images. The `add-temadag` and `audit-calendar` skills write the user's answer to the property, and `CONTEXT.md` names the emoji as part of a temadag.

## Manual steps
None.

## Verification
- `php index.php --debug` for every date of 2030, with git's SHA masked, prints byte-identical output before and after the move.
- With a fixture holding `"emoji": false`, no `emoji`, and `":semla:"`, `--debug` shows the bare title for the first two and `C :semla:` for the third.
- The `fields` test fails for fixtures with `"emoji": null`, `"emoji": "semla"`, an emoji in the title, and a 145-character title plus `:semla:`.
- `bash .claude/skills/audit-calendar/audit.sh` lists exactly the 20 temadagar without an `emoji` property and exits 1; one temadag with `"emoji": false` and working links and image exits 0.
- `composer run test`, `composer run lint:json` and `composer run lint:style` pass.
