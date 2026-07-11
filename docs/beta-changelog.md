# Beta changelog

The Testing Feedback modal reads a static, public-safe artifact from `storage/app/changelog.json`. Normal web requests only parse this file and never execute Git.

Generate it after source checkout and before serving a deployment:

```sh
php artisan changelog:generate --limit=50
```

The command stores only a short hash, commit timestamp, and filtered subject. It excludes merge commits and configured low-value prefixes/patterns. If Git metadata is unavailable (for example, a source bundle without `.git`), the command exits successfully with a warning and preserves the previous artifact. In that environment, generate the file in CI and include or transfer it with the deployment artifact.
