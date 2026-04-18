# Versioning And Releases

This project uses:

```text
MAJOR.FEATURE.BUG
```

Examples:

- `1.0.0` to `2.0.0`: major release.
- `1.0.0` to `1.1.0`: feature release.
- `1.0.0` to `1.0.1`: bug-fix release.

The current version is stored in:

```text
VERSION
```

Release notes are stored in:

```text
CHANGELOG.md
```

## Branch Naming

Use:

```bash
git checkout -b feature/add-new-report
git checkout -b bugfix/fix-user-filter
git checkout -b major/upgrade-laravel
git checkout -b docs/update-self-help
```

The version helper reads branch names, PR titles, and commit messages.

Use the highest-impact label when a change fits more than one category. Major wins over feature, and feature wins over bug fix.

Examples:

- adding a new worker service template: feature
- fixing a broken worker path: bug fix
- changing deployment structure in a risky way: major
- explaining how deployment works: docs

## Commit Prefixes

Use:

```bash
git commit -m "feature: add transaction report export"
git commit -m "fix: correct user permission filter"
git commit -m "major: upgrade Laravel runtime"
git commit -m "docs: update GitHub help"
```

## Release Flow

Update the version:

```bash
echo 1.1.0 > VERSION
```

Move notes from `Unreleased` in `CHANGELOG.md` into the new version section.

Commit:

```bash
git add VERSION CHANGELOG.md
git commit -m "chore: release v1.1.0"
git push
```

After merge to `main`, GitHub Actions creates the matching tag and GitHub release if it does not exist yet.
