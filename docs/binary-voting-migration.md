# Binary voting migration

Guide My Journey now treats each active `user_picks` row as one Guide supporting one Request. The existing unique index on `(user_id, recommendation_id)` remains the identity and race-safety boundary. `vote_count` remains temporarily for historical compatibility, but active rows must equal `1` and runtime ranking counts rows rather than summing quantities.

## Historical cutoff policy

- Active rows with a positive `vote_count` are normalized to `1`.
- Invalid active rows with a non-positive quantity are removed because they represent no support.
- Existing released rows retain their legacy quantity and supporter identity.
- Existing `vote_total_at_close` values are preserved without recalculation.
- Requests closed after this release snapshot the unique supporter count, so their vote total and supporter count use binary semantics.
- Existing earned accolades are retained. Current supported-publication and influence evaluators already count distinct Request IDs.

This intentionally creates a documented historical cutoff: an older closed Request may show its frozen legacy weighted total, while all active and newly closed Requests use unique supporters. Do not rewrite frozen totals unless an exact, separately approved supporter-level backfill is available.

## Required pre-deployment audit

Run against production before applying the schema migration:

```sh
php artisan votes:migrate-to-binary --dry-run
```

Review multi-vote rows, weighted versus unique totals, and the reported ranking-position changes. Take a database backup and record the current schema/index state before continuing.

## Laravel Cloud release commands

After the production backup is confirmed, use this release sequence in the Laravel Cloud deploy command:

```sh
php artisan votes:migrate-to-binary --dry-run
php artisan votes:migrate-to-binary --apply
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

The apply command is transactional and idempotent. The migration then adds the active binary check and the `(recommendation_id, released_at)` lookup index. Do not run `migrate` before the apply command on a database that still contains active quantities above one.

## Backup and rollback

Create and verify a Laravel Cloud database backup/snapshot before the apply step. Also export `user_picks` and the close-snapshot columns from `recommendations` if your database plan supports a separate logical dump.

Application rollback is safe with normalized `vote_count = 1` rows, but it cannot reconstruct discarded weighting. To restore the old weighted model exactly, roll back the application and restore the pre-apply database backup together. Do not infer old quantities. The schema migration can be rolled back only after the application rollback:

```sh
php artisan migrate:rollback --step=1 --force
php artisan optimize
php artisan queue:restart
```

## Post-deployment checks

- Re-run `php artisan votes:migrate-to-binary --dry-run`; multi-vote active rows must be zero.
- Confirm duplicate vote attempts remain one row and unvote is idempotent.
- Compare public and Creator Dashboard Most Voted ordering.
- Publish one supported staging Request and confirm one notification per Guide.
- Monitor Laravel logs, queue failures, unique/check-constraint errors, vote endpoint throttling, and slow ranking queries.
- Verify light/dark and mobile/desktop vote states in a connected browser before production sign-off.
