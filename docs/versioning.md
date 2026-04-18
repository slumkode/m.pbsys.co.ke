# Versioning

This project uses semantic versioning in the form:

```text
MAJOR.FEATURE.BUG
```

That maps to the common `MAJOR.MINOR.PATCH` format:

- Major: breaking changes, risky database migrations, framework upgrades, authentication/permission contract changes, or infrastructure changes that require manual rollout planning.
- Feature: backward-compatible new functionality, new screens, new reports, new permissions, or new integrations.
- Bug: backward-compatible fixes, small corrections, regressions, security hardening, or documentation-only corrections.

The current version is stored in `VERSION`.

## Branch Names

Use one of these prefixes:

- `major/short-description`
- `feature/short-description`
- `bugfix/short-description`
- `hotfix/short-description`
- `docs/short-description`

Examples:

```bash
git checkout -b feature/transaction-report-export
git checkout -b bugfix/user-permission-filter
git checkout -b major/laravel-upgrade
```

## Pull Requests

Every pull request should mark one change type:

- Major change
- Feature
- Bug fix
- Documentation or operations only

If a PR includes multiple types, choose the highest-impact type. Major wins over feature, and feature wins over bug fix.

GitHub Actions also reads the branch name, PR title, and commit subjects, then comments with a suggested next version. Use clear prefixes so the workflow does not have to guess:

- `major/...`, `major:`, `breaking:`, or a conventional commit with `!`
- `feature/...`, `feat/...`, `feature:`, or `feat:`
- `bugfix/...`, `hotfix/...`, `fix:`, `bug:`, or `patch:`
- `docs/...`, `docs:`, `chore:`, or `ops:` when no release bump is needed

## Release Steps

1. Update `VERSION`.
2. Move entries from `CHANGELOG.md` `[Unreleased]` into the new version section.
3. Commit the version update.
4. Tag the release with a `v` prefix.
5. Push the commit and tag.

Example:

```bash
echo "1.1.0" > VERSION
git add VERSION CHANGELOG.md
git commit -m "chore: release v1.1.0"
git tag -a v1.1.0 -m "Release v1.1.0"
git push origin main --tags
```

## Version Bumps

- `1.0.0` to `2.0.0`: major release.
- `1.0.0` to `1.1.0`: feature release.
- `1.0.0` to `1.0.1`: bug-fix release.

Do not reuse a version number after a tag has been pushed.

On pushes to `main`, GitHub Actions reads `VERSION`. If the matching `vX.Y.Z` tag does not exist yet, it creates the tag and a GitHub release automatically. That means the release commit should already have the intended `VERSION` and `CHANGELOG.md` entries.
