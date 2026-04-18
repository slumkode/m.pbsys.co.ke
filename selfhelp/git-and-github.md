# Git And GitHub

This project can be managed with plain Git. You do not need `gh` for normal work.

## First Setup

Check the current branch and remote:

```bash
git branch --show-current
git remote -v
```

Set the branch to `main`:

```bash
git branch -M main
```

Add the GitHub remote:

```bash
git remote add origin https://github.com/slumkode/m.pbsys.co.ke.git
```

If `origin` already exists:

```bash
git remote set-url origin https://github.com/slumkode/m.pbsys.co.ke.git
```

Push the first time:

```bash
git push -u origin main
```

## Daily Work

Start from an updated `main`:

```bash
git checkout main
git pull origin main
```

Create a branch using the change type:

```bash
git checkout -b feature/add-new-report
```

Other examples:

```bash
git checkout -b bugfix/user-permission-filter
git checkout -b major/laravel-upgrade
git checkout -b docs/github-self-help
```

Stage and commit:

```bash
git status --short
git add .
git status --short
git commit -m "feature: add report setup help"
```

Push the branch:

```bash
git push -u origin feature/add-new-report
```

Open GitHub in the browser and create a pull request.

## Login With HTTPS

When GitHub asks for a password over HTTPS, use a personal access token instead of your account password.

Token page:

```text
https://github.com/settings/tokens
```

For a classic token, include:

- `repo`
- `workflow`

Keep the token private. Do not paste it into `.env`, docs, scripts, or commits.

## Common Fixes

Remote already exists:

```bash
git remote set-url origin https://github.com/slumkode/m.pbsys.co.ke.git
```

Undo staged files without deleting them:

```bash
git restore --staged .
```

Remove a sensitive file from Git tracking while keeping it locally:

```bash
git rm --cached .env
```

Check ignored files:

```bash
git status --short --ignored
```
