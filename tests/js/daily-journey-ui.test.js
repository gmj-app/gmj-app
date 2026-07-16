import test from 'node:test';
import assert from 'node:assert/strict';
import { COUNTDOWN_STEPS, formatHud, GAME_UI_STATES, GameUiStateMachine, submissionPresentation } from '../../resources/js/daily-journey/ui-state.js';

test('all required UI states are registered', () => {
    for (const state of ['loading', 'ready', 'countdown', 'playing', 'paused', 'game_over', 'submitting', 'accepted', 'suspicious', 'rejected', 'submission_failed', 'exited']) assert.ok(GAME_UI_STATES.includes(state));
});

test('the normal run state sequence is valid', () => {
    const states = []; const machine = new GameUiStateMachine(({ state }) => states.push(state));
    for (const state of ['ready', 'countdown', 'playing', 'game_over', 'submitting', 'accepted']) machine.transition(state);
    assert.deepEqual(states, ['ready', 'countdown', 'playing', 'game_over', 'submitting', 'accepted']);
});

test('contradictory overlays cannot transition directly', () => {
    const machine = new GameUiStateMachine(null); machine.transition('ready');
    assert.throws(() => machine.transition('paused'), /Invalid game UI transition/);
});

test('submission state rejects duplicate submission transitions', () => {
    const machine = new GameUiStateMachine(null); machine.transition('ready'); machine.transition('countdown'); machine.transition('playing'); machine.transition('game_over'); machine.transition('submitting');
    assert.throws(() => machine.transition('submitting'), /Invalid game UI transition/);
});

test('countdown has the exact 3 2 1 Go sequence', () => assert.deepEqual([...COUNTDOWN_STEPS], ['3', '2', '1', 'GO!']));

test('pause and resume retain a valid playing state', () => {
    const machine = new GameUiStateMachine(null); machine.transition('ready'); machine.transition('countdown'); machine.transition('playing'); machine.transition('paused'); machine.transition('playing');
    assert.equal(machine.state, 'playing');
});

test('score and distance use stable whole-number formatting', () => assert.deepEqual(formatHud({ score: 1234567.8, distance: 98765.4 }), { score: '1,234,567', distance: '98,765m', shield: 'empty' }));
test('shield inactive state is explicit', () => assert.equal(formatHud({ shield: 'empty' }).shield, 'empty'));
test('shield ready state is explicit', () => assert.equal(formatHud({ shield: 'ready' }).shield, 'ready'));
test('shield broken state is explicit', () => assert.equal(formatHud({ shield: 'broken' }).shield, 'broken'));

test('accepted run uses the server rank', () => assert.match(submissionPresentation({ status: 'accepted', score: 900, rank: { rank: 7 } }).body, /#7/));
test('personal best uses the server-returned score', () => assert.match(submissionPresentation({ status: 'accepted', score: 1240, personal_best: true, rank: { rank: 4 } }).title, /1,240/));
test('daily leader receives number one messaging', () => assert.equal(submissionPresentation({ status: 'accepted', rank: { rank: 1 } }).eyebrow, 'YOU’RE #1 TODAY!'));
test('suspicious copy is neutral and excluded from ranking', () => assert.equal(submissionPresentation({ status: 'suspicious' }).state, 'suspicious'));
test('rejected copy does not accuse the player', () => assert.doesNotMatch(submissionPresentation({ status: 'rejected', reference: 'DJ123' }).title, /cheat/i));
test('network failure includes a supplied support reference', () => assert.match(submissionPresentation({}, { payload: { reference: 'DJ123' } }).body, /DJ123/));
