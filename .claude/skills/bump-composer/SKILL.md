---
name: bump-composer
description: Bump every composer dependency to its latest PHP-8.1-compatible version and pin constraints as ^n.n.n.
disable-model-invocation: true
---

Follow the "Dependencies" rules in `AGENTS.md` throughout.

1. Branch off an up-to-date `main` as `chore/bump-composer-deps`. Done when `git status` is clean on the new branch.
2. Run `composer outdated --direct` and keep its table as the **before** state.
3. Run `composer update -W`.
4. **Pin**: list installed versions with `composer show --direct`, drop any leading `v`, then run `composer require --no-update vendor/pkg:^x.y.z` per package (`--dev` for `require-dev`), then `composer update --lock`. Done when every constraint in `composer.json` is `^` plus the exact installed version.
5. **Blockers**: run `composer outdated`. For every package still listed, run `composer why-not vendor/pkg <latest>` and note the blocker. Leave blocked packages where they are; transitive ones stay out of `composer.json`. Done when every listed package has a named blocker.
6. Run `composer run lint:php`, `composer run lint:json` and `composer run test`. Done when all three are green.
7. Commit as `build: bump composer deps` and open a PR with the before/after table and the blocker list.
