# Daily Journey Challenge — Phase 1 operations

## Private-development access

The feature is fail-closed by default. With `DAILY_JOURNEY_PUBLIC_ENABLED=false`, only authenticated users recognized by the canonical `SUPER_ADMIN_EMAILS` allowlist may render the homepage preview, access game/leaderboard endpoints, issue or submit runs, receive game awards/notifications, or see game accolade tracks. Non-admin authenticated requests to game URLs return 404; unauthenticated web requests follow Laravel's normal login redirect and JSON requests return 401.

Set both variables in Laravel Cloud; never place real addresses in source control:

```dotenv
SUPER_ADMIN_EMAILS=admin@example.com
DAILY_JOURNEY_PUBLIC_ENABLED=false
```

After either value changes, rebuild cached configuration and restart long-running workers:

```sh
php artisan config:cache
php artisan queue:restart
```

## Architecture

The homepage renders only a lightweight preview and countdown. Clicking Play dynamically imports `resources/js/daily-journey/index.js`; that chunk imports Phaser and creates a 1280×720 FIT-scaled canvas. The production build measured 93.91 kB (34.73 kB gzip) for the shared initial JavaScript and 1,220.35 kB (336.64 kB gzip) for the lazy game chunk. Phaser is therefore absent from the initial execution path.

The single Phaser gameplay scene owns ready, play, pause, and game-over states. All art is generated from Phaser primitives at runtime: a procedural stick-figure runner, hills, trail, rock, fire, trail sign, pit, Journey Star, and shield. There are no licensed media assets.

The runner keeps an invisible 80×90 Arcade Physics carrier with the original gravity, velocity, overlap, and submission behavior. Its standing collision body is 56×82 at offset (12,8); crouching uses 56×42 at offset (12,48), keeping the same bottom edge. `ProceduralRunnerCharacter` owns one Phaser Container and three reusable Graphics objects for the figure, shield, and shield-break ring. Pure pose logic selects ready, running, jumping, falling, crouching, hit, and game-over states, while the scene remains authoritative for movement and collisions. The renderer API can later be implemented by a sprite-sheet character without changing gameplay logic.

Keyboard controls are Space/Up to jump, Down to duck, P/Escape to pause, and M to mute. Large touch controls use pointer events and disable browser gesture handling inside the controls. Focus loss and a hidden document pause active time. Portrait works as a scaled fallback with a landscape recommendation. Reduced-motion users do not receive smooth scrolling. Audio uses short synthesized effects after interaction and stores only the mute preference locally.

The seeded xorshift32 generator chooses one fair, separated pattern at a time. Hazards are rock, fire, overhead sign, and pit. Stars add 25 points. A shield absorbs one non-pit hazard and cannot stack. Speed is `min(720, 340 + 7 × active_seconds)` pixels/second; distance is integrated as pixels / 10, and score is `floor(distance_metres) + stars × 25`. Gaps narrow from the configured 390–680 pixel range as speed grows. All tuning is in `config/daily_journey.php`.

## Server authority and competition

`game_days` stores immutable Asia/Manila calendar boundaries. A session is issued with a UUID token, server seed, version, owner, expiry, and game day. It can be consumed once. The old day accepts its own already-issued session for five minutes after midnight; finalization waits for that grace period. New sessions always use the new Manila day.

Every attempt is retained in `game_runs`. Accepted attempts transactionally update `game_daily_bests`, the indexed one-row-per-user ranking projection. Order is score descending, distance descending, accepted time ascending, then row ID. `game_daily_champions` permanently snapshots one winner per finalized day. Public queries expose only Guide display name, handle, avatar, profile URL, score, distance, and rank.

Validation checks ownership, one-time use, expiry/grace, supported version, maximum run duration, integrated distance tolerance, exact score formula, collectible density, shield counts, speed tier, and a maximum 120-event compact digest. Impossible values are rejected and never projected. The browser cannot be made perfectly cheat-proof; the seed, raw attempts, validation flags, and Super Admin review preserve an audit trail.

Finalization locks and claims the day, writes the unique champion, updates the winner snapshot, and marks it finalized. Notification/accolade delivery is deduplicated by the champion row and notification key. The existing accolade engine evaluates `guide_daily_challenge_wins` at 1, 3, 10, and 25 wins. Super Admin can filter/review runs, see validation metadata, invalidate with a required reason, and recalculate an open-day best. A finalized-day invalidation deliberately reports that historical repair is required rather than silently rewriting awards.

Public leaderboard endpoints are limited to 60 requests/minute; session issuance is 20/minute and start/finish are 30/minute in addition to one-time consumption. Shared responses never cache user-specific bests. Phase 1 currently performs one indexed ranking query plus eager-loaded Guide identities on the homepage; the top list is capped at 25. Targeted cache invalidation hooks are present in the leaderboard service, while database projection remains the source of truth.

## Commands and scheduler

- `php artisan game:ensure-current-day`
- `php artisan game:finalize-days`
- `php artisan game:expire-sessions`
- `php artisan game:audit-day --date=YYYY-MM-DD` (read-only)

The Laravel scheduler runs ensure/expiry every five minutes and finalization every minute, each without overlap. The app's existing queue worker remains required for other application jobs; champion persistence does not depend on an asynchronous job. Winner inbox delivery is durable and deduplicated.

## Laravel Cloud deployment

Run from the release environment:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Verify the managed scheduler with `php artisan schedule:list`, then run `php artisan game:ensure-current-day` and `php artisan game:audit-day`. Confirm the Cloud scheduler invokes `php artisan schedule:run` every minute. Verify the configured worker with `php artisan queue:monitor default` (or Cloud's worker health panel), dispatch an existing test notification, and confirm `php artisan queue:failed` is empty.

Rollback application code normally, but do not drop the five game tables after production scores exist. The migration is additive; its `down()` is intended only for an unused/test deployment. If code rollback is required, leave historical tables intact and disable the homepage entry through the deployed view/config. Database restore is required before any destructive rollback.

## Verification and limitations

Automated coverage includes guest/auth access, Manila boundary creation, accepted and tampered submissions, one-time sessions, best projection, idempotent finalization, one champion, and one winner notification. The full Laravel suite and Vite production build are the release gates. Manual QA should cover keyboard/touch, all four hazards, shield, focus pause, themes, landscape/portrait, leaderboard navigation, Super Admin invalidation, and a staged midnight/grace transition.

Known Phase 1 limitations: generated placeholder art and synthesized effects are intentionally simple; no music, replay renderer, multiplayer, store, character selection, or all-time UI exists. Anti-cheat is plausibility-based rather than tamper-proof. Historical champion repair after invalidating a finalized winner remains a deliberate operator workflow rather than a one-click UI.
