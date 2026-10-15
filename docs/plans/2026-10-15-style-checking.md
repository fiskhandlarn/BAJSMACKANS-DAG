# Style checking

## Context
Issue #20. CI linted PHP syntax and the data file but checked no style. `.editorconfig` set 2-space indent for every file, while the PHP under `src/` and `tests/` used 4 and `index.php` used 2.

## Change
`friendsofphp/php-cs-fixer` and `armin/editorconfig-cli` are dev dependencies; no Node tooling. `.php-cs-fixer.dist.php` applies `@PER-CS` to every PHP file git does not ignore, which leaves out `vendor/` and `tests/Support/_generated/`. Its cache file is in `.gitignore`.

`ec` checks every tracked file against `.editorconfig` and skips binaries such as `fika.png` on its own. It runs without `--strict`: strict mode flags docblock alignment and the `LICENSE` text. So `ec` catches tabs, trailing whitespace, missing final newlines and line endings, but not a 4-space indent where 2 is expected.

`.editorconfig` adds `indent_size = 4` sections for `*.php` and for `composer.lock`, which composer writes with 4 spaces. Everything else stays at 2. PHP lines may be at most 80 characters (`max_line_length`). `@PER-CS` sets no hard limit, so `ec` checks it, and long lines are wrapped by hand: neither tool wraps them.

`composer.json` gets two scripts. `lint:style` runs `php-cs-fixer fix --dry-run --diff`, then `ec --git-only`, and fails on any violation. `fix:style` runs `php-cs-fixer fix`, then `ec --git-only --fix`.

The `lint` job in `.github/workflows/pull-request.yml` sets up PHP 8.1, like the `test` job, and runs `composer run lint:style`.

`AGENTS.md` tells agents to run `fix:style` before committing.

A separate commit reformats the repo: `fix:style` over every file, plus `.vscode/extensions.json`, `codeception.yml` and `tests/Cli.suite.yml` moved from 4 to 2 spaces by hand.

## Manual steps
None.

## Verification
- `composer run lint:style`, `lint:php`, `lint:json` and `test` pass.
- A 2-space indent in a PHP file, or trailing whitespace in a YAML or Markdown file, makes `lint:style` fail.
- `composer run fix:style` fixes the PHP violation.
- The pull request's `lint` job passes.
