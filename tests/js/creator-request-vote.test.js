import test from 'node:test';
import assert from 'node:assert/strict';
import { creatorRequestVote } from '../../resources/js/creator-request-vote.js';

const response = (payload, { ok = true, status = 200 } = {}) => ({
    ok,
    status,
    json: async () => payload,
});

const clickEvent = () => {
    const state = { prevented: false, stopped: false };

    return {
        state,
        preventDefault: () => { state.prevented = true; },
        stopPropagation: () => { state.stopped = true; },
    };
};

test('voting is optimistic, canonical, and independent from expansion', async () => {
    let resolveRequest;
    const calls = [];
    const request = (url, options) => {
        calls.push({ url, options });

        return new Promise((resolve) => { resolveRequest = resolve; });
    };
    const state = creatorRequestVote({
        requestId: 8,
        requestTitle: 'Collapsed request',
        authenticated: true,
        votable: true,
        hasVoted: false,
        votes: 42,
        voteUrl: '/vote',
        csrfToken: 'token',
        request,
    });
    state.expandedRequestId = 1;
    const event = clickEvent();

    const mutation = state.toggleVote(event, 'collapsed_blade');

    assert.equal(state.hasVoted, true);
    assert.equal(state.votes, 43);
    assert.equal(state.votePending, true);
    assert.equal(state.expandedRequestId, 1);
    assert.deepEqual(event.state, { prevented: true, stopped: true });
    assert.equal(calls[0].options.method, 'POST');

    resolveRequest(response({ has_voted: true, votes: 44 }));
    await mutation;

    assert.equal(state.hasVoted, true);
    assert.equal(state.votes, 44);
    assert.equal(state.voteAnnouncement, 'Vote added. 44 votes.');
    assert.equal(state.expandedRequestId, 1);
});

test('rapid repeated activation sends only one mutation', async () => {
    let resolveRequest;
    let requests = 0;
    const state = creatorRequestVote({
        authenticated: true,
        votable: true,
        votes: 0,
        voteUrl: '/vote',
        request: () => {
            requests++;

            return new Promise((resolve) => { resolveRequest = resolve; });
        },
    });

    const first = state.toggleVote(clickEvent());
    await state.toggleVote(clickEvent());
    assert.equal(requests, 1);

    resolveRequest(response({ has_voted: true, votes: 1 }));
    await first;
});

test('removal never makes the optimistic count negative', async () => {
    const state = creatorRequestVote({
        authenticated: true,
        votable: true,
        hasVoted: true,
        votes: 0,
        voteUrl: '/vote',
        request: async (url, options) => {
            assert.equal(options.method, 'DELETE');

            return response({ has_voted: false, votes: 0 });
        },
    });

    await state.toggleVote(clickEvent());

    assert.equal(state.hasVoted, false);
    assert.equal(state.votes, 0);
    assert.equal(state.voteAnnouncement, 'Vote removed. 0 votes.');
});

test('a failed mutation restores the prior canonical state', async () => {
    const state = creatorRequestVote({
        authenticated: true,
        votable: true,
        hasVoted: false,
        votes: 7,
        voteUrl: '/vote',
        request: async () => response(null, { ok: false, status: 500 }),
    });

    await state.toggleVote(clickEvent());

    assert.equal(state.hasVoted, false);
    assert.equal(state.votes, 7);
    assert.equal(state.voteError, true);
    assert.equal(state.voteAnnouncement, 'We couldn’t update your vote. Try again.');
});

test('guest activation follows the login URL without mutating state', async () => {
    let destination = null;
    const state = creatorRequestVote({
        authenticated: false,
        votable: true,
        hasVoted: false,
        votes: 5,
        loginUrl: '/login/required',
        navigate: (url) => { destination = url; },
    });

    await state.toggleVote(clickEvent());

    assert.equal(destination, '/login/required');
    assert.equal(state.hasVoted, false);
    assert.equal(state.votes, 5);
});
