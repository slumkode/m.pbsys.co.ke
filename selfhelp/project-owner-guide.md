# Project Owner Guide

This page is the normal operating guide for managing the project on GitHub.

## The Big Picture

Use this flow:

```text
local branch -> push to GitHub -> pull request -> GitHub Actions -> merge to main -> deploy
```

`main` is the stable branch. Do not do everyday work directly on `main`.

Feature, bug fix, and documentation branches are working branches:

- `feature/...`: new behavior or new setup.
- `bugfix/...`: a correction to something broken.
- `major/...`: breaking or risky change.
- `docs/...`: documentation only.

## Your Current Feature Branch

When Git says:

```text
feature/add-new-report is ahead of main
```

that is normal. It means the branch contains commits that are not merged into `main` yet.

Push it:

```bash
git push -u origin feature/add-new-report
```

Then create a pull request on GitHub:

```text
feature/add-new-report -> main
```

Wait for Actions to pass before merging.

## Daily Routine

Start from a fresh `main`:

```bash
git checkout main
git pull origin main
```

Create a branch:

```bash
git checkout -b feature/short-description
```

Make changes, then check the repo:

```bash
git status --short --ignored
```

Stage and commit:

```bash
git add .
git status --short
git commit -m "feature: describe the change"
```

Push:

```bash
git push -u origin feature/short-description
```

Open a pull request in GitHub.

## What To Check Before Merge

Before merging into `main`, confirm:

- GitHub Actions passed.
- The secret guard did not find sensitive data.
- The changed files look expected.
- `.env`, `.vscode/`, logs, caches, `vendor/`, and `node_modules/` are not included.
- `VERSION` and `CHANGELOG.md` are updated if this is a release.
- Deployment secrets exist if the change will deploy.

## Login Activity And Audit Logs

The app records login activity with free, built-in tools:

- Laravel auth/session events record login, logout, remembered sessions, IP changes, and last page visited.
- Browser location uses the standard browser geolocation permission prompt. Nothing is stored unless the browser/user allows it.
- No paid tracking service is required.

Review the data here:

```text
Audit Logs > View Changes
```

For authentication events, the details panel shows the linked login session, IP address, browser, platform, device type, last page, and browser location if it was permitted.

If a user changes IP address during a session, the app writes an `Ip Changed` audit entry. If the browser reports a different location, the app writes `Location Recorded` or `Location Changed`.

## GitHub Token

Do not save your GitHub token in the project.

For HTTPS Git, paste the token only when Git asks for a password:

```text
Username: slumkode
For the password prompt, paste the token only into the terminal prompt.
```

If Windows cached a bad token, remove it:

```text
Control Panel > Credential Manager > Windows Credentials > github.com
```

Then push again.

## Server SSH Password

The server SSH password is different from the GitHub token.

Do not put the SSH password in:

- `.env`
- `.env.example`
- docs
- scripts
- workflow files

Put it in GitHub:

```text
Repository > Settings > Secrets and variables > Actions > New repository secret
```

Use this secret name:

```text
DEPLOY_PASSWORD
```

The deploy workflow also supports `DEPLOY_SSH_KEY`. Use one or the other. If both are present, the workflow prefers the SSH key.

## GitHub Plugin, GH CLI, And MCP

You do not need a GitHub plugin, `gh`, or MCP to manage this project.

Plain Git is enough for:

- pushing branches
- creating pull requests in the browser
- triggering GitHub Actions
- merging after checks pass

VS Code GitHub extensions are optional. They make PRs and Actions easier to view inside VS Code, but GitHub still works without them.

`gh` is optional. It is useful for managing PRs and Actions from the terminal, but it is not required.

MCP is optional. It would only help an assistant inspect GitHub more directly. It is not required for Actions or deployment.

## When Something Goes Wrong

If a commit is blocked:

```bash
git status --short --ignored
```

Read the pre-commit output. It prints file names and line numbers, but hides secret values.

If Actions fail:

```text
GitHub repo > Actions > failed run > failed job > failed step
```

Fix locally, commit, and push again.
