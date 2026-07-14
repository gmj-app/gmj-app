# Performance audit

Audited 2026-07-14. Measurements are local PHPUnit/SQLite measurements and are intended for relative comparison, not as production latency claims.

## Measured creator-page baseline

Fixture: guest request, 30 active requests, 10 supporters per request, first 25 requests rendered.

| Metric | Before | After |
| --- | ---: | ---: |
| Application queries | 21 | 14 |
| Database query time | 2.35 ms | 1.71 ms |
| Response generation | 263.76 ms | 61.43 ms |
| HTML size | 791.0 KB | 245.6 KB |
| Incremental peak memory | 8.0 MB | 6.0 MB |

The dominant bottleneck was not asset compilation. The initial Blade response rendered 25 complete hidden request cards and eagerly materialized every vote, supporter, and supporter accolade. The optimized response renders only folded rows. A personalized server-rendered detail partial is fetched on first expansion and retained for the page session.

## Route audit

| Surface | Bound | Finding |
| --- | ---: | --- |
| Homepage | 12 creators | Top requests previously loaded every active request for the 12 creators and truncated in PHP. It now uses a three-per-creator eager limit (36 maximum). |
| Creator queue | 25 requests | Bounded, but hidden details and unbounded supporter relationships were the primary issue. Fixed. |
| Published | 25 requests | Now bounded with server-side pagination and preserved search parameters. |
| Closed | 25 requests | Bounded and ordered by resolution date. |
| Public Guide profile | 10 suggestions + 10 supports | Bounded; published highlights are limited to six. |
| My Hub | Bounded projections | Uses service-level grouped aggregates; continue monitoring authenticated header work. |
| My Activity | 25 creators, 5 votes and 10 suggestions per creator | Bounded service projections. |
| Notifications | 25 | Bounded; the global bell performs an unread count plus ten recent rows on every authenticated page. |
| Private Accolades | Track projections | Definition repository is cached; subject awards/progress are scoped queries. |
| Super Admin requests | 25 | Bounded. Detail history is limited to 20. |

Browser LCP, CLS, request count, and production database plans were not reproducible in the automated environment. Capture these in Laravel Cloud/staging after deployment with a warm application and representative data.

## Implemented changes

- Deferred full request cards to `requests.card-details`; public and viewer-specific data are never shared through a page cache.
- Removed unbounded supporter/vote eager loading from the initial queue.
- Limited expanded supporter previews to ten while retaining aggregate totals.
- Changed folded YouTube thumbnails from `hqdefault.jpg` to `mqdefault.jpg`; images retain lazy loading, async decoding, and dimensions.
- Bounded homepage top-request results to three per creator at the database relationship query.
- Added compound indexes for public creator/status/date queries, active vote aggregates, and active favorites.
- Removed global `Cache::flush()` calls from creator/request mutations. Those calls evicted unrelated YouTube metadata and accolade definitions; no creator-page fragment cache currently requires invalidation.
- Added opt-in slow-request logging with duration, route, query count/time, status, memory, and user ID, but no request body.
- Verified `php artisan optimize` successfully caches configuration, events, routes, and views.

## Production settings and deployment

Set `APP_ENV=production`, `APP_DEBUG=false`, `PERFORMANCE_LOG_ENABLED=true`, and initially set `PERFORMANCE_SLOW_REQUEST_MS=1000`. Keep the database queue worker and scheduler running. Confirm OPcache is enabled in the runtime.

Recommended deployment tail, after environment variables and dependencies are available:

```sh
php artisan migrate --force
npm run build
php artisan optimize
php artisan queue:restart
```

Do not end a production deployment with `optimize:clear`; it discards the caches just built. Hashed Vite assets should be served with long-lived immutable headers by the platform. Personalized HTML must not receive shared public caching.

## Local/staging load plan

Create factory data only in a disposable database. Test a small set (2 creators, 100 requests each, 10 votes/request), medium set (100 creators, one creator with 1,000 requests and 100 supporters on popular requests), then large adoption simulation (50,000 guides, 10,000 requests, 500,000 votes, several thousand favorites). Do not model external subscriber counts as application users.

Exercise the creator queue, detail partial, filters, vote endpoint, submission, homepage, notifications, Published, and Closed routes with k6 or wrk. Record p50/p95/p99 latency, error rate, database CPU, slow queries, queue depth, and memory. Stop if errors rise or the staging database saturates. Never run this plan against production.

## Budgets and next thresholds

- Creator initial response: at most 25 rows, no expanded-card markup, at most 20 application queries in the regression fixture, under 350 KB HTML, warm server target below 800 ms.
- Homepage: 12 creators and no more than three top requests per creator; warm target below 600 ms.
- Detail partial: no more than ten supporter records; paginate a future full supporter list.
- Consider Redis only when measured database-cache contention, queue throughput, or multi-instance coordination requires it.
- Consider denormalized creator counters only if indexed aggregate queries remain a measured bottleneck at production scale; provide a reconciliation command first.

## Remaining work

1. Move notification dropdown contents behind an on-demand request; retain a cheap indexed unread count.
2. Capture production `EXPLAIN` plans after the new indexes deploy.
3. Measure authenticated creator pages separately; membership limit methods currently issue several small queries.
4. Add controlled caching only after slow-request logs identify stable repeated work. Do not cache full personalized HTML.
