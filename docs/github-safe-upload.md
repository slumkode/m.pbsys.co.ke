# GitHub Safe Upload

This project is safe to put on GitHub when only source code and placeholder configuration are committed. Real server values belong in the server `.env` file or in GitHub Actions secrets, never in the repository.

## What Is Protected

- `.env`, `.env.*`, and `.vscode/` are ignored.
- Laravel runtime files in `storage/` and generated cache files in `bootstrap/cache/` are ignored.
- Private keys, database dumps, and backup files are ignored.
- GitHub Actions runs a Laravel CI check on PHP 7.4, uses `composer.phar` when present, runs PHPUnit, builds frontend assets, blocks private runtime files, and scans the repository with Gitleaks.
- Pull requests get a short human-readable comment explaining whether the upload passed or what needs attention.
- New server setup lives in `scripts/install-stack.sh`, with passwords supplied at runtime instead of committed.
- GitHub account and Actions setup steps live in [GitHub Account And Actions Setup](github-account-actions.md).

## First Push

Run these from the project root:

```bash
git init
git config core.hooksPath .githooks
git add .
git status --short --ignored
git commit -m "chore: add Laravel safety checks"
git branch -M main
git remote add origin git@github.com:YOUR-USER/YOUR-REPO.git
git push -u origin main
```

Check `git status --short --ignored` before the first commit. You should see `.env`, `.vscode/`, `storage/` runtime files, `bootstrap/cache/*.php`, `vendor/`, and `node_modules/` as ignored instead of staged.

## If Files Were Already Tracked

If this project was already initialized as a Git repository before the ignore rules were added, remove sensitive/runtime files from Git tracking without deleting your local copies:

```bash
git rm --cached .env
git rm -r --cached .vscode storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache
git add .gitignore .github .githooks docs bootstrap/cache/.gitignore storage
git commit -m "chore: keep private runtime files out of git"
```

If any real credential was ever pushed to GitHub, rotate it immediately. Removing it in a later commit does not remove it from Git history.

## GitHub Secrets

Use repository secrets for values needed by Actions:

- Go to `Settings > Secrets and variables > Actions`.
- Add only the values that CI or deployment needs.
- Keep production database, RabbitMQ, mail, M-Pesa, and Jenga credentials out of committed files.

The safe pattern is simple: commit `.env.example` with empty placeholders, keep `.env` private, and let the workflow complain loudly if something sensitive slips into a commit.

For server packages, PHP extensions, Composer usage, Node version, and worker commands, read [System Requirements](system-requirements.md).
