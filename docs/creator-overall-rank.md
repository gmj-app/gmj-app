# Canonical Creator Request ranks

## Implementation

Changed files:
- `app/Models/Recommendation.php`: shared canonical ordering and `withOverallCreatorRank()` scope.
- `app/Http/Controllers/RecommendationController.php`: public list starts from the ranked universe.
- `resources/views/recommendations/creator-queue.blade.php`: consumes `overall_rank`; adds an accessible overall-rank label. Existing classes and layout remain intact.
- `tests/Feature/CreatorOverallRankTest.php`: filter/sort, pagination/performance, eligibility/tie/support regressions.
- This report.

The actual bug was the Blade expression `($recommendations->firstItem() ?? 1) + $loop->index`. This enumerated the filtered, sorted paginator, conflating result position with rank. It also changed badges when selecting another sort.

The canonical universe reuses `activePubliclyVisible()` for the selected Creator and Eloquent's soft-delete scope. It includes approved, coming_soon, scheduled, and recorded Requests whose Creator is available for Guides. Pending, published, merged_duplicate, hidden, withdrawn, already_seen, passed, and soft-deleted Requests are excluded. No new moderation/status interpretation was introduced.

Exact ordering: `user_picks_count DESC, created_at ASC, id ASC`. Positions are sequential and deterministic (ROW_NUMBER), including ties. Both the existing Most Voted ordering scope and the window use one shared ordering definition.

Vote totals reuse `withEffectiveVoteTotal()` unchanged. Open voting counts active support rows; the existing unique user/request constraint and binary-vote enforcement make these unique supporting Guides. Closed voting preserves the existing frozen `vote_total_at_close`, with its existing historical fallback. This task does not migrate or reinterpret historical snapshots/quantities.

The database query has three layers:
1. Select the complete eligible Creator universe with the authoritative effective vote total.
2. Apply `ROW_NUMBER() OVER (...) AS overall_rank` over that universe.
3. Apply search/tag/category/status filters, selected display order, and SQL pagination outside the ranked derived table.

The outer Eloquent table alias remains `recommendations`, preserving existing relationship predicates and personalized support queries. There are no PHP rank lookups, per-result queries, full-model PHP ranking, new persisted fields, or migrations. The new view-model field is `overall_rank`; filtered result summaries continue to use the paginator.

Search (including title, display/source titles, artist/channel, and URL), Christmas/other tags, category, status, and combinations only choose rows. Most Voted, Newest, Status, and Scheduled are the supported public sorts; all retain canonical badges. Public Oldest/Alphabetical options were not added. The Creator management page supports Oldest but does not display rank badges; neither it nor the dashboard gains badges in this change.

All page sizes (10/25/50/100), including page 2, retain global ranks. The responsive layouts share the same badge markup and backend value; no viewport-specific calculation exists. Voting JavaScript and stable-list behavior are unchanged. Subsequent requests recompute rank from current totals. Request lists are queried directly rather than cached here; existing targeted mutation cache invalidation is unchanged.

## Performance and indexes

No query-count increase is introduced by the nested SQL. Regression measurements over 160 Requests and 107 Christmas matches used 14 queries for each of page 1/page 2 at 10, 25, 50, and 100 rows. The existing 50-row creator-page test used 15 queries; default-list test used 13. Ranking, filters, limits, and offsets execute in SQL.

Migration audit found existing Creator/status/deleted_at and Creator/status/created_at composite indexes, Request primary key, support `(recommendation_id, released_at)` index, unique `(user_id, recommendation_id)` support constraint, unique Creator/tag slug, and unique Request/tag pivot pair plus foreign-key indexes. Category is a scalar Request field, not a category pivot. No new category index or rank cache was justified by this scoped change. Existing indexed predicates remain in the inner universe/support and outer tag queries. No live MySQL EXPLAIN or production index inspection was performed; window execution was tested on SQLite, not MySQL 8.4.

## Verification

- `php artisan test --compact`: **620 passed, 5654 assertions**.
- New ranking regression tests: **3 passed, 775 assertions**.
- `npm.cmd run test:creator-accordion`: **13 passed** (accordion and voting).
- `vendor\bin\pint --dirty`: passed, with formatting applied.
- `php artisan view:cache`: passed after final Blade change.
- `npm.cmd run build`: passed (Vite production build).
- `git diff --check`: passed.

The filter regression fixture has 12 Requests with different effective totals. Christmas/search/category/Recorded matching Requests preserve ranks **2, 4, 6, 8, 10, 12** instead of **1, 2, 3, 4, 5, 6**. Newest reverses their display order to **12, 10, 8, 6, 4, 2**, preserving badge values and the filtered count of six. Tests cover combined filters, repeated loads, exact date/ID ties, excluded states, other Creators, requester support, and refresh after adding/releasing support.

The initial HTML-budget failure was resolved by preserving LF line endings and retaining only the accessible label, without the duplicate tooltip attribute. The existing HTML-size assertion passes unchanged.

## Actual-data and browser QA boundary

Read-only JFragment QA could not run: the configured local `database/database.sqlite` returns `SQLSTATE[HY000]: General error: 11 database disk image is malformed` on the Creator lookup, both inside and outside sandbox restrictions. No database repair or data mutation was attempted. Consequently there are no verified actual JFragment before/after Christmas ranks or actual-data search results to report; the examples above are automated fixtures.

Browser inventory returned no enabled browsers/apps. Interactive desktop/mobile and real Guide voting QA were not performed. Shared badge markup and automated interaction regressions were verified, which does not substitute for viewport screenshots. No deployment was performed.
