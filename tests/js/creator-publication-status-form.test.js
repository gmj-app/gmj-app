import assert from 'node:assert/strict';
import test from 'node:test';

import { creatorPublicationStatusForm } from '../../resources/js/creator-publication-status-form.js';

test('published status fills a blank publication time in its own form', () => {
    const form = creatorPublicationStatusForm('recorded', '2026-08-01T14:30');
    form.status = 'published';
    form.$refs = { publishedAt: { value: '' } };

    form.statusChanged();

    assert.equal(form.$refs.publishedAt.value, '2026-08-01T14:30');
    assert.equal(form.publicationTimeAnnouncement, 'Publication date set to current time');
});

test('published status never overwrites an existing publication time', () => {
    const form = creatorPublicationStatusForm('recorded', '2026-08-01T14:30');
    form.status = 'published';
    form.$refs = { publishedAt: { value: '2026-07-20T09:15' } };

    form.statusChanged();

    assert.equal(form.$refs.publishedAt.value, '2026-07-20T09:15');
    assert.equal(form.publicationTimeAnnouncement, '');
});
