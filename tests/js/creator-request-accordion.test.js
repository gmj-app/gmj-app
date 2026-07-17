import test from 'node:test';
import assert from 'node:assert/strict';
import { creatorRequestAccordion, visibleRequestIdFromHash } from '../../resources/js/creator-request-accordion.js';

test('the server-selected first visible request is the only initial expansion', () => {
    const accordion = creatorRequestAccordion([41, 42, 43], 41);

    assert.equal(accordion.expandedRequestId, 41);
});

test('opening another request replaces the expanded stable request ID', () => {
    const accordion = creatorRequestAccordion([41, 42, 43], 41);

    accordion.toggleRequest(42);

    assert.equal(accordion.expandedRequestId, 42);
});

test('collapsing the current request leaves every request collapsed', () => {
    const accordion = creatorRequestAccordion([41, 42], 41);

    accordion.toggleRequest(41);
    assert.equal(accordion.expandedRequestId, null);

    accordion.toggleRequest(42);
    accordion.toggleRequest(42);
    assert.equal(accordion.expandedRequestId, null);
});

test('a user-collapsed first request is not automatically reopened', () => {
    const accordion = creatorRequestAccordion([41, 42], 41);

    accordion.toggleRequest(41);

    assert.equal(accordion.expandedRequestId, null);
});

test('a visible deep link takes priority and malformed or hidden IDs do not', () => {
    assert.equal(visibleRequestIdFromHash('#recommendation-42', [41, 42]), 42);
    assert.equal(visibleRequestIdFromHash('#recommendation-99', [41, 42]), null);
    assert.equal(visibleRequestIdFromHash('#recommendation-nope', [41, 42]), null);

    const accordion = creatorRequestAccordion([41, 42], 41);
    assert.equal(accordion.openHashRequest('#recommendation-42'), true);
    assert.equal(accordion.expandedRequestId, 42);
    assert.equal(accordion.openHashRequest('#recommendation-99'), false);
    assert.equal(accordion.expandedRequestId, 42);
});

test('an empty result set is safe and cannot expand an unknown request', () => {
    const accordion = creatorRequestAccordion([], null);

    accordion.toggleRequest(41);

    assert.deepEqual(accordion.requestIds, []);
    assert.equal(accordion.expandedRequestId, null);
});
