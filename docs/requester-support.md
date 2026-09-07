# Automatic requester support

Public `RecommendationController::store` validates the submission, checks the existing duplicate URL protection, locks the authenticated User inside a transaction, enforces favorites and the effective per-Creator Request limit, creates the fan Request, creates its canonical support, attaches any Christmas tag, and commits. `RequestCreated` workflows still dispatch after creation. No internal HTTP vote call is made.

`RequestSupportService::supportRequest` extracts the normal vote endpoint's `firstOrCreate` operation. Both paths use this method. The existing database unique constraint on `(user_id, recommendation_id)` prevents duplicate rows; new rows have `vote_count = 1`. Existing rows are untouched. Failure to insert support rolls back the Request. Existing submission protections remain unchanged: this does not introduce general form-idempotency tokens, and separate topic submissions remain separate Requests.

Public approved Requests immediately render their authoritative supporter total and selected vote button alongside “You requested.” The existing compact pressed button/checkmark is the voted state; no layout or copy redesign was made. Normal requester unvote and re-vote remain available. Page loads never recreate support. Pending Requests receive support immediately inside the same transaction, without bypassing moderation visibility; approval does not create another row.

The authenticated public submitter is the canonical requester (`submitted_by`, source `fan`), including Guides with per-Creator limit overrides. Creator starter suggestions and Super Admin starter creation use source `creator` and do not auto-vote. No separate on-behalf-of public submission path was found. Factories, seeders, and arbitrary model creation do not trigger automatic support; there is no global model observer.

Duplicate merging already unions active supporter identities and handles overlap once. Automatic support participates normally. Status closing/freezing and hidden history policies are unchanged. Publication supporter notifications already exclude the requester; requester-specific publication notifications remain, with existing notification deduplication. No additional vote-click notification or event is emitted at submission. `RequestCreated` already evaluates Guide submission and Creator reach; reach unions supporter and submitter identities. No vote-source analytics field exists on the reused operation.

Support publication accolades now include real requester support. Their distinct Request calculation counts it once; combined influence already unions submitted and supported Request IDs. Submission accolades remain separate. The publication accolade listener now evaluates requester support too; notification recipient exclusions remain unchanged.

After commit, the existing targeted `RequestCacheInvalidator` clears Request, Creator list/metrics, requester activity/profile, search and homepage ranking keys. Queue counts, selected votes and supporter previews query canonical relationships. No global cache flush or ranking special case was added.

## Backfill

```shell
php artisan requests:backfill-requester-support --dry-run
php artisan requests:backfill-requester-support --apply
```

No flag defaults to dry-run. Conflicting flags fail. Review the dry-run before an explicit apply. Apply is an operator-run normalization, not a scheduled task: old deliberate unvotes cannot be distinguished from missing legacy support, so repeatedly applying it after later unvotes would restore those votes.

Eligibility is a real existing requester, source `fan`, Pending/Approved status, no deletion, no resource release, no voting closure, and no removed moderation status. Coming Soon, Scheduled and Recorded already close voting in this application, so they are excluded alongside Published, Passed, Already Seen, Hidden, Withdrawn and Merged. Existing inactive support rows are separately reported and preserved rather than reactivated. Frozen totals and historical rows are never rewritten.

Apply locks the User and rechecks the locked Request before inserting, uses the shared unique support operation, and invalidates affected caches after commit. Repeated apply with unchanged data inserts nothing. There is no migration and no automatic production backfill.

The configured local database dry-run failed before producing totals:

```text
SQLSTATE[HY000]: General error: 11 database disk image is malformed
Connection: sqlite, Database: C:\laragon\www\gmj-mvp\database\database.sqlite
```

Consequently actual active/historical totals are unavailable, and no persistent apply or database repair was performed. Isolated test fixtures contain two eligible missing supports, nine excluded closed Requests and one creator-sourced Request: dry-run creates zero rows, apply creates two binary rows, and a second apply retains exactly two rows.

## Changed files and verification

- `app/Http/Controllers/RecommendationController.php`: transactional initial support and canonical interactive vote reuse.
- `app/Services/RequestSupportService.php`: shared idempotent insert.
- `app/Console/Commands/BackfillRequesterSupport.php`: audit and explicit apply.
- `app/Listeners/EvaluateAccoladesAfterRequestPublished.php` and `app/Services/Accolades/Evaluators/GuideSupportedPublicationEvaluator.php`: count legitimate requester support.
- `tests/Feature/RequesterSupportTest.php`: submission, pending support, selected rendering, vote/unvote, rollback, idempotent backfill, frozen exclusion and accolade/recipient checks.
- `tests/Feature/PhaseThreeAccoladeTest.php` and `tests/Feature/AccoladeTestingToolkitTest.php`: update previous self-support exclusion expectations.
- This report.

Focused requester support tests: 4 passed, 35 assertions. Full Laravel suite: 617 passed, 4,879 assertions. Pint (including new files), diff checks, Blade compilation and production Vite build passed. Interactive browser/manual QA and staging backfill were not completed because the configured database is unreadable. No deployment performed. Request blade markup, CSS, pagination, collapsed defaults, tags, moderation and routing were not changed.
