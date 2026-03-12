# Codacy preparation report

## Added
- `.codacy.yml`
- `.phpcs.xml.dist`
- `phpstan.neon.dist`
- `.editorconfig`
- `.gitattributes`
- `CODE_QUALITY_SETUP.md`

## Updated
- `composer.json`
  - added `lint:all`
  - added `phpcs`
  - added `phpstan`

## Purpose
This change prepares the repository for a more realistic quality gate by excluding generated/runtime/template-heavy paths and by standardizing local analysis.

## Important
Codacy still needs two manual UI actions after pushing this update:
1. Enable **Only fail on new issues**
2. Set the current state as **baseline**
