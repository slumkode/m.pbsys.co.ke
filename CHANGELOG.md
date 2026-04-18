# Changelog

All notable changes to this project should be recorded here.

This project uses semantic versioning:

- Major: breaking changes or risky infrastructure changes.
- Feature: backward-compatible functionality.
- Bug fix: backward-compatible fixes and small corrections.

## [Unreleased]

### Major

- Nothing yet.

### Features

- Added `APP_DIR` to the environment template and installer `.env` sync for fresh server installs.
- Added GitHub Actions version suggestions for pull requests and automatic release tags from `VERSION` on `main`.
- Added GitHub account setup documentation and a sanitized Apache/Laravel deployment script.
- Expanded the local pre-commit scanner to catch `DB_PASS` and similar secret key names.
- Added self-help documentation for Git, GitHub Actions, hooks, `.gitignore`, and releases.
- Added a lightweight GitHub setup smoke test for CI.
- Added a production deploy workflow that runs tests before upload and refreshes Laravel cache, config, views, migrations, and queues on the server.
- Expanded self-help into a project-owner guide and server-operations guide.
- Added a tracked systemd template for the M-Pesa C2B worker service.
- Added password-based SSH deployment support through the `DEPLOY_PASSWORD` GitHub Actions secret.
- Allowed Laravel runtime `.gitignore` keeper files in GitHub Actions and made Composer validation friendlier for the Laravel 7 project.

### Bug Fixes

- Nothing yet.

### Security

- Nothing yet.

## [1.0.0] - 2026-04-18

### Features

- Added the first GitHub-ready project baseline with Laravel CI, secret scanning, safe-upload guidance, system requirements, and new-installation stack documentation.
