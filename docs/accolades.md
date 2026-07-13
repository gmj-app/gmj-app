# Accolade engine

Phase 3A uses config-backed definitions (`config/accolades.php`) and database-backed earned/progress records. Definition keys are immutable contracts; names, descriptions, art, thresholds, and activation can evolve by definition version.

## Permanent-earned model

`user_accolades` is append-only achievement history for normal product workflows. Moderation, an unfavorite, or a later progress decrease does not revoke an earned accolade. `accolade_progress.current_value` is a rebuildable snapshot of currently valid domain data and is never the source of truth. Public display uses the larger of current progress and the highest earned threshold to avoid presenting an earned level as incomplete.

Founding Guide and OG Guide remain driven by the existing stable `guide_number` rules and are adapted into the new profile section. Their assignments, number ranges, and gold/silver avatar treatment are unchanged.

## Event and notification boundary

Request creation/publication, favorite addition, and vote allocation queue idempotent evaluations after commit. `AccoladeAwarded` is dispatched once for each newly inserted earned row and carries the earned record ID, stable key, owner user ID, subject type/ID, track, level, timestamp, and source context. Phase 3A intentionally registers no notification listener. Phase 3B can consume the event; backfill events include `source=backfill` and `suppress_notifications=true`.

## Operations

Preview historical awards with `php artisan accolades:backfill --dry-run`; persist with `--apply`. Use subject, user, email, creator, track, and chunk filters as needed. `php artisan accolades:rebuild-progress --apply` repairs snapshots without granting or revoking awards. Both commands are idempotent.
