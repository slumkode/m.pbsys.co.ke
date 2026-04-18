# Self Help

This folder is for day-to-day GitHub help for this project. It is meant to be committed and uploaded to GitHub so the same instructions are available from any machine.

Start here:

- [Project Owner Guide](project-owner-guide.md): the full owner workflow for branches, tokens, PRs, Actions, and merge decisions.
- [Git And GitHub](git-and-github.md): first push, daily branches, commits, remotes, and common fixes.
- [GitHub Actions](github-actions.md): what the workflows do, how to read failures, and how to rerun checks.
- [Git Hooks And Gitignore](githooks-and-gitignore.md): local secret protection, ignored files, and safe staging.
- [Server Operations](server-operations.md): production deployment, cache refresh, worker service setup, and server checks.
- [Versioning And Releases](versioning-and-releases.md): major, feature, bug-fix versions, tags, and release flow.

Quick safety rule:

```bash
git status --short --ignored
```

Before every first push or big commit, confirm `.env`, `.vscode/`, runtime logs, cache files, `vendor/`, and `node_modules/` are ignored.
