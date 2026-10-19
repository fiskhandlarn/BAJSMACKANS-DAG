---
name: audit-calendar
description: Audit every temadag in the calendar (links, image, title) and propose fixes for what broke.
disable-model-invocation: true
---

Terms follow `CONTEXT.md`. Every fix meets the rules of the **Links**, **Image**, **Emoji** and **Preview** steps in `.claude/skills/add-temadag/SKILL.md`. The user decides every fix; you research and propose.

1. **Branch**: start on a new branch `fix/calendar-audit` off an up-to-date `main`. Done when `git status` is clean on the new branch.
2. **Audit**: run `bash .claude/skills/audit-calendar/audit.sh`. It prints one tab-separated line per problem: kind (`link`, `image`, `title`), title, problem, url. It exits 1 on `link` and `image` lines only; `title` lines are suggestions and come back on every run, declined or not. Done when the user has every problem, grouped by temadag.
3. Per temadag with problems, in turn:
   1. **Links**: a 3xx whose target (`curl -sL -o /dev/null -w '%{url_effective}'`) is the same page gets the target as its fix. Any other broken link gets a replacement by the **Links** rules. Done when every offered set answers HTTP 200 and fits the subtitle limits.
   2. **Image**: a broken or oversized image gets a fix by the **Image** rules; for a Wikimedia thumbnail that starts with the same file at a standard width. Done when you have looked at every image you offer and given the user each one's full URL.
   3. **Title**: for a title with no trailing emoji, ask per the **Emoji** step; "no emoji" is an answer. Done when the user has answered.
   4. **Preview** and **confirm** each changed field by name. Done when the user has approved or declined every fix for this temadag.
   5. **Commit**: run `composer run lint:json`, `composer run lint:style` and `composer run test`, then commit `days.json` alone, one commit per kind of fix: `fix: replace <links|image> of <title without emoji>`, `feat: add emoji to <title>`. Done when all three checks are green and the commits exist.
4. **Pull request**: open it once every problem from step 2 is fixed or declined. List the declined ones in the body. Done when the PR is open.
