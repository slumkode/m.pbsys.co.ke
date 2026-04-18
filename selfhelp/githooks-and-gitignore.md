# Git Hooks And Gitignore

This project uses both local hooks and GitHub Actions to reduce the chance of leaking secrets.

## Local Hook

The hook lives here:

```text
.githooks/pre-commit
```

Enable it:

```bash
git config core.hooksPath .githooks
```

What it blocks:

- `.env` and `.env.*` except `.env.example`
- `.vscode/`
- Laravel runtime logs and cache output
- database dumps
- private keys and certificates
- lines that look like filled credentials

If the hook blocks a commit, read the file and line it prints. It hides the secret value on purpose.

## Gitignore

Important ignore rules live in:

```text
.gitignore
```

The important ignored paths are:

- `.env`
- `.env.*`
- `.vscode/`
- `vendor/`
- `node_modules/`
- `storage/logs/*`
- `storage/framework/sessions/*`
- `storage/framework/views/*`
- `storage/framework/cache/data/*`
- `bootstrap/cache/*.php`
- `*.sql`, `*.sqlite`, `*.dump`
- `*.key`, `*.pem`, `*.p12`, `*.pfx`

Small `.gitignore` keeper files under `storage/` are allowed so Laravel runtime folders exist after clone.

## Safe Staging Checklist

Run:

```bash
git status --short --ignored
```

Good ignored lines look like:

```text
!! .env
!! .vscode/
!! storage/logs/laravel.log
!! bootstrap/cache/services.php
```

Bad staged lines look like:

```text
A  .env
A  .vscode/sftp.json
A  storage/logs/laravel.log
A  database.sql
```

Unstage bad files:

```bash
git restore --staged .env .vscode storage/logs database.sql
```

## Emergency Bypass

Git allows bypassing hooks:

```bash
git commit --no-verify
```

Avoid that for normal work. GitHub Actions will still run the secret guard after push, and a leaked secret must be rotated even if it is removed later.
