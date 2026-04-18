# GitHub Account And Actions Setup

I cannot connect myself to your GitHub account directly from here unless your machine/server already has GitHub credentials available. The clean setup is to authenticate Git locally, push this repository, and let GitHub Actions run the workflow files in `.github/workflows/`.

## Local Computer Setup

Install GitHub CLI, then authenticate:

```bash
gh auth login
gh auth status
```

If you prefer SSH, make sure your SSH key is added to GitHub:

```bash
ssh -T git@github.com
```

Then create or connect the repository:

```bash
git remote add origin git@github.com:YOUR-USER/YOUR-REPO.git
git branch -M main
git push -u origin main
```

If the GitHub repo already exists and `origin` is already set:

```bash
git remote -v
git push -u origin main
```

## GitHub Actions

Actions will run automatically after the workflow files are pushed. This project includes:

- Laravel safety checks in `.github/workflows/laravel-safety.yml`
- Version suggestions and release tagging in `.github/workflows/versioning.yml`
- Pull request comments for human-readable status
- Secret scanning before code is accepted

On GitHub, open the repository and go to `Actions` to see runs. If Actions are disabled, enable them for the repository.

## Repository Secrets

Do not push `.env`. Add deployment-only values in GitHub repository settings when a workflow needs them:

- `Settings > Secrets and variables > Actions`
- Add only values needed by CI or deployment
- Keep production database, mail, RabbitMQ, M-Pesa, and Jenga values private

Typical secret names:

- `DEPLOY_HOST`
- `DEPLOY_USER`
- `DEPLOY_SSH_KEY`
- `APP_URL`
- `DB_PASSWORD`
- `RABBITMQ_PASSWORD`
- `MPESA_TRANSACTION_STATUS_CREDENTIAL`

The current CI workflow does not need production secrets to test the app.

## Branch Protection

Recommended protection for `main`:

- Require pull request before merging
- Require status checks to pass
- Require the Laravel safety checks
- Do not allow force pushes
- Require conversation resolution

## Working With Me

Once `gh auth status` works on this machine, you can give me the repository name or URL and I can help run the local Git commands. I should not receive or store your GitHub password or private tokens in files.
