# Code quality setup for GitHub + Codacy

This repository now includes a pragmatic baseline for local quality checks and for reducing noisy Codacy results.

## Added files
- `.codacy.yml`
- `.phpcs.xml.dist`
- `phpstan.neon.dist`
- `.editorconfig`

## What this setup does
- Excludes runtime, generated, upload, legacy, and documentation paths from repository-level quality analysis.
- Focuses static analysis on maintainable PHP application code instead of templates, generated assets, and storage files.
- Keeps local tooling aligned with the same reduced scope.

## Recommended Codacy repository settings
These two steps still need to be done in the Codacy UI:

1. Enable **Only fail on new issues**.
2. Set the current repository state as the **baseline**.

Without those two UI settings, Codacy can still fail the build because of thousands of historical findings that are outside the scope of this repository cleanup.

## Suggested local commands
```bash
composer install
vendor/bin/phpcs --standard=.phpcs.xml.dist
vendor/bin/phpstan analyse -c phpstan.neon.dist
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

## Notes
- The Codacy exclusions are intentionally conservative for this release.
- Template-heavy paths were excluded because the current ruleset flags many false positives around custom escaping helpers like `h()` and `e()`.
- If you later standardize templating and output escaping, these paths can be re-enabled gradually.
