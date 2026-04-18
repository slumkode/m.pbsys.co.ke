# GitHub Actions

GitHub Actions runs automatically after you push commits or open a pull request.

The owner should use Actions as the gatekeeper. If Actions fail, do not merge into `main` and do not deploy to the server.

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

Production deploy:

```text
.github/workflows/deploy-production.yml
```

What it does:

- Runs pre-deploy checks first.
- Installs PHP dependencies.
- Boots Laravel.
- Runs PHPUnit.
- Builds frontend assets.
- Uploads files only after checks pass.
- Keeps server `.env` and runtime storage out of the upload.
- Runs Composer on the server.
- Clears Laravel cache, config, routes, and views.
- Runs migrations with `--force`.
- Rebuilds config and view cache.
- Restarts queues.

## How To Check Results

Open:

```text
https://github.com/slumkode/m.pbsys.co.ke/actions
```

Click the newest workflow run. Open the failed job, then open the failed step.

## Deployment Secrets

The production deploy workflow needs these repository secrets:

- `DEPLOY_HOST`: server IP or host name.
- `DEPLOY_PORT`: SSH port, usually `22`.
- `DEPLOY_USER`: SSH user, for example `root` or a deploy user.
- `DEPLOY_PATH`: server project path, for example `/var/www/m.pbsys.co.ke`.
- `DEPLOY_SSH_KEY`: private SSH key that can log in to the server.
- `DEPLOY_PASSWORD`: SSH password for password-based login.

Use either `DEPLOY_SSH_KEY` or `DEPLOY_PASSWORD`. SSH keys are preferred, but password login is supported if that is how the server is configured. If both are present, the workflow uses the SSH key.

Add them in GitHub:

```text
Repository > Settings > Secrets and variables > Actions > New repository secret
```

Do not commit these values to the repository.

If you are using an SSH password, put it only in the GitHub secret named `DEPLOY_PASSWORD`.

## Deployment Flow

Feature branches should not deploy directly.

Normal flow:

```bash
git checkout -b feature/add-new-report
git add .
git commit -m "feature: add new report"
git push -u origin feature/add-new-report
```

Then open a pull request. GitHub Actions tests the branch. After the pull request is merged into `main`, the production deploy workflow runs pre-deploy tests again, uploads the code, then refreshes Laravel on the server.

Manual deploy is also available from the GitHub Actions tab, but run it from the `main` branch.

## Actions Before Server Upload

The deployment workflow is intentionally split into checks first, deploy second.

If any of these fail, upload does not happen:

- Composer validation
- Composer install
- Laravel environment preparation
- route boot check
- PHPUnit
- frontend build

Only after those pass does the workflow connect to the server, upload code, clear Laravel cache/config/views, run migrations, and restart queues.

## Common Failures

Secret guard fails:

- Check whether `.env`, `.vscode/`, logs, cache, database dumps, or private keys were staged.
- Run `git status --short --ignored`.
- Remove bad files from tracking with `git rm --cached path/to/file`.
- Small `.gitignore` keeper files inside Laravel runtime folders are allowed.

Composer fails:

- Check `composer.json` and `composer.lock`.
- The workflow uses `composer.phar` when it exists.
- The workflow validates Composer files without publish-only strictness because this is an older Laravel 7 application.

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
