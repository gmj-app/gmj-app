# Guide-authored Request context

The canonical current Guide-authored context is `recommendations.reason`.
Submission (`submit.blade.php`, `StoreRecommendationRequest`, and
`RecommendationController::store`) already writes that column directly. The
Guide presentation form now reads `old('reason', $recommendation->reason)` and
updates the same column through `RequestPresentationService`. Both submission
and edit accept nullable plain text up to 2,000 characters. Display-title
fallback and its 160-character limit are unchanged.

`recommendations.description` is different: the submission form uses it for the
required topic definition. `RequestIdentityComparator` protects it as linked
content identity. It is not copied into the optional motivation textarea.

## Legacy presentation context

The presentation-editing migration introduced `request_context` as a separate
column. Previously the edit textarea only read/wrote that column, while the
initial submission populated `reason`. Neither had a connecting accessor. This
caused existing submission text to appear missing on Edit and allowed two
different current descriptions on the expanded card.

New Guide edits never write `request_context`. Existing nonblank values are not
merged, overwritten, or deleted. They remain historical data, labeled "Earlier
presentation context" in Creator and Super Admin management. Public cards use
only `reason` for current motivation; clearing it never falls back to legacy
text. Existing administrative clear/revert actions can still manage the legacy
override without clearing the canonical submission reason.

No migration or bulk backfill is needed. There is no automatic conflict
resolution: preserving the submitted reason and separately retaining legacy
text avoids choosing or concatenating potentially conflicting user content.
The local read-only count/length audit on 2026-09-10 could not complete because
SQLite reported `database disk image is malformed`. Actual legacy counts and
over-limit values are therefore unknown. Opening Edit does not truncate or save
any value; text over the limit remains visible and must satisfy validation on
an explicit description save.

## Revisions and integrity

Existing revision text columns (`previous_request_context` and
`new_request_context`) store before/after text. `changed_fields` distinguishes
new `reason` revisions from older `request_context` revisions. Revert restores
the corresponding changed text column; title-only reverts do not change either
description. Actor, timestamp, action, and title snapshots retain the existing
audit conventions. A Guide save preserves old legacy context even when both
columns contain different text.

The original requester policy still applies, including its existing fan-source,
status, moderation, and soft-delete restrictions. The Guide endpoint gives no
special bypass to another user who is a Super Admin. Separate administrative
permissions remain unchanged. Routine context saves create no correction,
notification, support, status, ranking, or request-slot change.

Current public cards/details, published details, and existing management inputs
already use `reason`; the Creator context preview and Super Admin context
summary now do too. Shared cards carry the change into activity/history and
comparisons. Historical revision/report records remain untouched. Existing
targeted presentation and search cache keys are invalidated; the homepage title
cache is only invalidated when the display title changes.

## Changed files

- `app/Http/Controllers/GuideRequestPresentationController.php`: whitelist the two editable fields.
- `app/Http/Controllers/SuperAdmin/CreatorRequestController.php`: include current reason in revert audit metadata.
- `app/Http/Requests/StoreRecommendationRequest.php`: align context limit to 2,000.
- `app/Http/Requests/UpdateOwnRequestPresentationRequest.php`: validate canonical reason and reject obsolete context input.
- `app/Services/RequestPresentationService.php`: save/audit canonical reason and retain old revision compatibility.
- `resources/views/recommendations/edit-presentation.blade.php`: prepopulate existing textarea from reason.
- `resources/views/recommendations/submit.blade.php`: align existing context counter and maximum.
- `resources/views/components/recommendation-card.blade.php`: remove the second, obsolete current-description display.
- `resources/views/creators/recommendations/index.blade.php`: show current reason and distinguish legacy context.
- `resources/views/super-admin/creators/requests/edit.blade.php`: distinguish current reason from legacy context.
- `tests/Feature/GuideRequestPresentationTest.php`: submission/edit/display, integrity, authorization, validation, legacy and audit regressions.
- `tests/Feature/SubmitRecommendationTest.php`: assert the shared 2,000-character motivation limit while retaining the separate topic-description limit.
- `docs/request-description-editing.md`: architecture audit and compatibility decisions.
