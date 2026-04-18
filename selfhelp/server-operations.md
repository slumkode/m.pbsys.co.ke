# Server Operations

This page explains how server deployment and worker services should be managed.

## Deployment Rule

Production should only deploy from `main` after tests pass.

Feature branches should be used for review and testing. They should not upload directly to the server.

## Deploy Workflow

The production workflow is:

```text
.github/workflows/deploy-production.yml
```

It has two jobs:

- `predeploy-checks`: installs dependencies, boots Laravel, runs PHPUnit, and builds assets.
- `deploy`: runs only after `predeploy-checks` passes.

The deploy job:

- uploads files with `rsync`
- excludes `.env`, `.git/`, `.github/`, `.vscode/`, `vendor/`, `node_modules/`, and runtime storage
- runs Composer on the server
- clears Laravel caches
- runs migrations
- rebuilds config and view cache
- restarts queues

## Required GitHub Secrets

Add these in GitHub:

```text
Repository > Settings > Secrets and variables > Actions > New repository secret
```

Required:

- `DEPLOY_HOST`: server IP or hostname.
- `DEPLOY_PORT`: SSH port, normally `22`.
- `DEPLOY_USER`: SSH user.
- `DEPLOY_PATH`: `/var/www/m.pbsys.co.ke`.
- `DEPLOY_SSH_KEY`: private key for the deploy user.
- `DEPLOY_PASSWORD`: SSH password for the deploy user.

Use either `DEPLOY_SSH_KEY` or `DEPLOY_PASSWORD`. SSH keys are cleaner, but password login is supported.

Do not commit these values.

## Server Environment

The server keeps its real values in:

```text
/var/www/m.pbsys.co.ke/.env
```

This file must stay on the server. GitHub uploads must not overwrite it.

The deployment workflow excludes `.env`, so production credentials stay in place.

## Manual Laravel Refresh

If you need to refresh Laravel manually on the server:

```bash
cd /var/www/m.pbsys.co.ke
php composer.phar install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan view:cache
php artisan queue:restart
```

## M-Pesa C2B Worker

The worker should run under systemd.

The server service path is:

```text
/etc/systemd/system/mpbsys_mpesa_c2b_worker.service
```

The local `.vscode/` copy is ignored by Git. That is intentional because `.vscode/` can hold machine-specific files.

If you want a reusable tracked copy, keep it under:

```text
deploy/systemd/mpbsys_mpesa_c2b_worker.service
```

Install or update the worker on the server:

```bash
sudo cp deploy/systemd/mpbsys_mpesa_c2b_worker.service /etc/systemd/system/mpbsys_mpesa_c2b_worker.service
sudo systemctl daemon-reload
sudo systemctl enable --now mpbsys_mpesa_c2b_worker.service
sudo systemctl status mpbsys_mpesa_c2b_worker.service
```

Restart after deployment:

```bash
sudo systemctl restart mpbsys_mpesa_c2b_worker.service
```

View logs:

```bash
journalctl -u mpbsys_mpesa_c2b_worker.service -f
```

## Worker Change Type

Adding the service for the first time is a feature:

```bash
git checkout -b feature/add-mpesa-c2b-worker-service
git commit -m "feature: add M-Pesa C2B worker service"
```

Fixing a broken service is a bug fix:

```bash
git checkout -b bugfix/fix-mpesa-c2b-worker-service
git commit -m "fix: correct M-Pesa C2B worker service"
```

Documenting it only is documentation work:

```bash
git checkout -b docs/update-worker-service-help
git commit -m "docs: update worker service help"
```
