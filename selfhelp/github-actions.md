# GitHub Actions

GitHub Actions runs automatically after you push commits or open a pull request.

## Workflows In This Project

Laravel safety checks:

```text
.github/workflows/laravel-safety.yml
```

What it does:

- Checks that private runtime files are not tracked.
- Scans for leaked secrets with Gitleaks.
- Installs PHP 7.4 and Composer dependencies.
- Boots Laravel enough to list routes.
- Runs PHPUnit.
- Installs Node.js 14 dependencies.
- Builds frontend assets with `npm run prod`.
- Adds a human-readable PR comment.

Version helper:

```text
.github/workflows/versioning.yml
```

What it does:

- Reads branch name, PR title, and commit subjects.
- Suggests the next `MAJOR.FEATURE.BUG` version.
- Creates a `vX.Y.Z` tag and GitHub release when `VERSION` changes on `main`.

## How To Check Results

Open:

```text
https://github.com/slumkode/m.pbsys.co.ke/actions
```

Click the newest workflow run. Open the failed job, then open the failed step.

## Common Failures

Secret guard fails:

- Check whether `.env`, `.vscode/`, logs, cache, database dumps, or private keys were staged.
- Run `git status --short --ignored`.
- Remove bad files from tracking with `git rm --cached path/to/file`.

Composer fails:

- Check `composer.json` and `composer.lock`.
- The workflow uses `composer.phar` when it exists.

PHPUnit fails:

- Run the failing test locally on the server or a PHP 7.4 machine.
- Keep tests compatible with PHPUnit 8 and Laravel 7.

Node build fails:

- This project expects Node.js 14 for Laravel Mix 5.
- Check `package-lock.json` and `webpack.mix.js`.

Version helper cannot suggest a version:

- Use branch prefixes like `major/`, `feature/`, `bugfix/`, `hotfix/`, or `docs/`.
- Use commit prefixes like `major:`, `feat:`, `fix:`, or `docs:`.

## Rerun

Push a new commit to rerun Actions:

```bash
git commit --allow-empty -m "chore: rerun checks"
git push
```

You can also rerun from the GitHub Actions page in the browser.
