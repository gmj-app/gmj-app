# Accolade testing toolkit

## Local fixture setup

```powershell
php artisan db:seed --class=AccoladeDemoSeeder
```

The seeder deletes and recreates only `accolade.*@example.test` users and `accolade-*` creators. It refuses to run when `APP_ENV=production`. Fixture password: `password`. The application currently uses Google-only browser sign-in, so the password is intended for automated/local tooling rather than the normal login form.

Each scenario has `below`, `exact`, and `above` variants:

| Scenario | Subjects | Values | Expected at exact threshold |
|---|---|---:|---|
| Submitted publications | `accolade.submitted.{boundary}@example.test` | 4 / 5 / 6 | Tenderfoot, Trailblazer, Scout, First Footprint |
| Supported publications | `accolade.supported.{boundary}@example.test` | 4 / 5 / 6 | Hiking Boots, Trail Map, First Footprint |
| Favorite creators | `accolade.favorites.{boundary}@example.test` | 2 / 3 / 4 | Explorer, Community Connector |
| Creator publications | creator `accolade-creator-publications-{boundary}`; owner `accolade.creator-publications.{boundary}@example.test` | 4 / 5 / 6 | First Step, Listener |
| Creator consistency | creator `accolade-creator-consistency-{boundary}`; owner `accolade.creator-consistency.{boundary}@example.test` | 2 / 3 / 4 months | Momentum at exact/above; First Step from source publications |
| Creator reach | creator `accolade-creator-reach-{boundary}`; owner `accolade.creator-reach.{boundary}@example.test` | 24 / 25 / 26 Guides | Gathering Crowd |

`{boundary}` is `below`, `exact`, or `above`. Above variants remain below the following launch threshold, except where their supporting publication count independently crosses another track threshold.

## Inspection

Commands are read-only unless `--evaluate` is present:

```powershell
php artisan accolades:test-subject --email=accolade.supported.exact@example.test --show-source-records --show-earned --show-progress
php artisan accolades:test-subject --creator=accolade-creator-consistency-exact --show-source-records --show-earned --show-progress
```

To persist missing awards and progress locally:

```powershell
php artisan accolades:test-subject --email=accolade.supported.exact@example.test --evaluate --show-source-records --show-earned --show-progress
```

Test-subject and backfill evaluations suppress `AccoladeAwarded`, guaranteeing that notification listeners cannot run. Unique database constraints keep repeated evaluations idempotent.

## Safe production verification

Never run the demo seeder in production. Inspect a real subject without mutation first:

```powershell
php artisan accolades:test-subject --email=real-guide@example.com --show-source-records --show-earned --show-progress
php artisan accolades:test-subject --creator=real-creator-slug --show-source-records --show-earned --show-progress
php artisan accolades:backfill --subject=guides --email=real-guide@example.com --dry-run --verbose
php artisan accolades:backfill --subject=creators --creator=real-creator-slug --dry-run --verbose
php artisan accolades:rebuild-progress --subject=guides --email=real-guide@example.com --dry-run --verbose
```

Production `accolades:test-subject --evaluate` requires an interactive confirmation. Prefer scoped backfill `--apply` only after reviewing the dry-run output.
