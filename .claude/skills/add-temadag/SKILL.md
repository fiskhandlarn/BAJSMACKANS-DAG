---
name: add-temadag
description: Add temadagar to days.json, named ones or the ones a source calendar has that we lack.
disable-model-invocation: true
---

Terms follow `CONTEXT.md`: calendar, source, temadag, day rule. The user decides every field; you research and propose.

1. **Branch**: an unpushed branch of temadagar that is checked out is the batch; keep adding to it. Otherwise start the batch on a new branch off an up-to-date `main`. Done when `git status` is clean on the batch branch.
2. **Candidates** (when the user names a source rather than a temadag): list every temadag on the source that `days.json` lacks, matching by meaning (the source's Våffeldagen is our "Jungfru Maria bebådelsedag"). Done when the user has picked which to add. When the user asks where to look, offer [Sources to search](#sources-to-search).
3. Per temadag, in turn:
   1. **Day rule**: find at least one source besides the one it came from, and confirm the date on each source's own page for the temadag; list-page summaries have misread months. A date printed for one year may be one year of a movable rule ("1 mars" was Shrove Tuesday 2022): resolve the candidate rule with `resolveDayRule()` in `src/days.php` for several years and compare. Done when two sources agree on the rule; when they disagree, find a tiebreaker.
   2. **Links**: an sv.wikipedia article first when one exists (look it up with the MediaWiki API on `sv.wikipedia.org/w/api.php`), then a source that states the date. The `fields` test in `tests/Cli/DaysCest.php` holds the subtitle limits; count the built subtitle (`<url|host>` joined by ` · `) for each set of links you offer. Done when every link answers HTTP 200 and every offered set fits.
   3. **Image**: a public URL answering HTTP 200 with an image content type, about 1 MB or less; Wikimedia's thumbnail URLs (`/thumb/…/960px-…`) shrink large originals; only standard widths such as 500px and 960px answer 200. Done when you have looked at every image you offer.
   4. **Title**: propose the spelling the sources share; when they spell it differently, ask with each spelling as an option.
   5. **Emoji**: ask whether to append a Slack emoji to the title. Offer standard emoji that fit; the user may answer with a custom workspace emoji.
   6. **Insert** into `days.json` in ascending date order. Movable rules open their month, before its fixed dates; easter-relative rules go first in the file, beside Fettisdagen.
   7. **Preview**: run `php .claude/skills/add-temadag/preview.php YYYY-MM-DD "Title"` for a date the rule resolves to, and give the user its JSON and its Block Kit Builder link. Done when the user has both.
   8. **Confirm** title, links and image with the user, each by name. Done when the user has approved all three.
   9. **Sources**: the list where the temadag was found belongs in `## Sources` in `README.md`: the calendar the user named, or the list research started from when the user named only the temadag. When it is missing, add it in its own `docs:` commit.
   10. **Commit**: run `composer run lint:json`, `composer run lint:style` and `composer run test`, then commit `days.json` alone as `feat: add <title without emoji> on <day rule in words>`. Done when all three are green and the commit exists.
4. **Pull request** for the batch branch, once the user says the batch is done.

## Sources to search

- https://temadagar.se, the user's first pick
- https://matkalender.nu
- https://www.kungsornen.se/tips-tricks/teman-bakning/
- https://matwebben.se/startsida/receptkategorier/tillfallen/
- https://vilkendag.nu/temadagar
- https://receptfavoriter.se/matartiklar/temadagar-for-mat
- https://dagensdatum.se, as a tiebreaker only: it gave Geléhallonens dag a wrong date
