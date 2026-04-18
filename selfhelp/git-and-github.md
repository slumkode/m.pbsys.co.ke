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

The user manages the project by moving work through pull requests. Do not treat a pushed feature branch as production-ready until Actions pass and the branch is merged into `main`.

## When A Feature Branch Is Ahead Of Main

That is normal. It means your branch has commits that `main` does not have yet.

Use this flow:

```bash
git push -u origin feature/add-new-report
```

Then open a pull request on GitHub:

```text
feature/add-new-report -> main
```

Let GitHub Actions pass. Review the files. Merge the pull request when it is ready.

After merge, update your local `main`:

```bash
git checkout main
git pull origin main
```

Then start the next branch from the updated `main`.

## Managing Pull Requests

On GitHub, open:

```text
https://github.com/slumkode/m.pbsys.co.ke/pulls
```

For each pull request:

- Read the changed files.
- Check the GitHub Actions result.
- Read the version suggestion comment.
- Confirm there are no secret files.
- Merge only when the PR is ready for `main`.

After merging, delete the remote feature branch from GitHub if it is no longer needed.

Locally, clean up old branches:

```bash
git checkout main
git pull origin main
git branch -d feature/add-new-report
```

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
