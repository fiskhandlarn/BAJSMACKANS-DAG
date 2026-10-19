---
name: bump-actions
description: Bump every GitHub Action in .github/workflows to its latest release, pinned by commit SHA.
disable-model-invocation: true
---

Dependabot (`.github/dependabot.yml`) opens these bumps weekly, one PR per action, and closes its own PRs once `main` has the version; this skill bumps all of them at once, now.

1. **Branch**: branch off an up-to-date `main` as `chore/bump-actions`. Done when `git status` is clean on the new branch.
2. **Before**: list every `uses:` line in `.github/workflows/` with its action, SHA and `# <version>` comment. Done when the table holds every `uses:` line; keep it as the **before** state.
3. Per action:
   1. **Latest**: `gh api repos/<owner>/<repo>/releases/latest --jq .tag_name`; an action with no releases (404) takes its highest semver tag from `gh api --paginate repos/<owner>/<repo>/tags --jq '.[].name'`. Done when you have the tag.
   2. **SHA**: `gh api repos/<owner>/<repo>/commits/<tag> --jq .sha` resolves the tag to its commit. The tag object's own SHA (from `git/refs/tags`) differs for annotated tags; the commit SHA is the pin. Done when you have the commit SHA.
   3. **Pin**: write `@<commit sha> # <tag>`, the tag copied verbatim, in every workflow using the action. Done when no workflow holds the old SHA.
   4. **Breaking changes**: on a major bump, read the release notes between the two versions. Done when you have noted every change touching an input, output or runner our workflows use.

   Done when every action is on its latest release, or stays put with a named reason.
4. **Pull request**: commit as `build: bump GitHub Actions` and open a PR with the before/after table and the breaking changes. `pull-request.yml` exercises its own actions on the PR. `daily.yml` runs only from the server crontab and posts to Slack, so name its changed actions in the PR body for the user to watch on the next daily run. Done when the PR's `lint` and `test` jobs are green.
