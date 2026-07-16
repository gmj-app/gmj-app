export const GAME_UI_STATES = Object.freeze([
    'loading', 'preparation_failed', 'ready', 'countdown', 'playing', 'paused',
    'restart_confirm', 'exit_confirm', 'game_over', 'submitting', 'accepted',
    'suspicious', 'rejected', 'submission_failed', 'exited',
]);
export const COUNTDOWN_STEPS = Object.freeze(['3', '2', '1', 'GO!']);

const TRANSITIONS = {
    loading: ['ready', 'preparation_failed', 'exited'],
    preparation_failed: ['loading', 'exited'],
    ready: ['countdown', 'preparation_failed', 'exit_confirm', 'exited'],
    countdown: ['playing', 'exited'],
    playing: ['paused', 'game_over', 'exit_confirm'],
    paused: ['playing', 'restart_confirm', 'exit_confirm'],
    restart_confirm: ['paused', 'loading'],
    exit_confirm: ['ready', 'playing', 'paused', 'exited'],
    game_over: ['submitting'],
    submitting: ['accepted', 'suspicious', 'rejected', 'submission_failed'],
    accepted: ['loading', 'exited'],
    suspicious: ['loading', 'exited'],
    rejected: ['loading', 'exited'],
    submission_failed: ['loading', 'exited'],
    exited: ['loading'],
};

export class GameUiStateMachine {
    constructor(onChange, initial = 'loading') {
        this.state = initial;
        this.onChange = onChange;
    }

    can(next) {
        return GAME_UI_STATES.includes(next) && (TRANSITIONS[this.state] || []).includes(next);
    }

    transition(next, payload = {}) {
        if (!this.can(next)) throw new Error(`Invalid game UI transition: ${this.state} -> ${next}`);
        const previous = this.state;
        this.state = next;
        this.onChange?.({ state: next, previous, payload });
        return next;
    }
}

export function submissionPresentation(result = {}, error = null) {
    if (error) {
        const reference = error.payload?.reference;
        return {
            state: 'submission_failed',
            eyebrow: 'CONNECTION LOST',
            title: 'Your score could not be submitted.',
            body: reference ? `Please try a new run. Reference: ${reference}` : 'Please check your connection and try a new run.',
        };
    }

    if (result.status === 'suspicious') {
        return { state: 'suspicious', eyebrow: 'SCORE UNDER REVIEW', title: 'Your run was saved but is not ranked yet.', body: 'A review can take place without affecting your next run.' };
    }

    if (result.status !== 'accepted') {
        return { state: 'rejected', eyebrow: 'SCORE NOT ACCEPTED', title: 'We couldn’t verify this run.', body: result.reference ? `You can start a new run. Reference: ${result.reference}` : 'You can start a new run.' };
    }

    if (result.rank?.rank === 1) {
        return { state: 'accepted', eyebrow: 'YOU’RE #1 TODAY!', title: 'Can anyone catch you before midnight?', body: 'Your score is on today’s leaderboard.' };
    }

    if (result.personal_best) {
        return { state: 'accepted', eyebrow: 'NEW PERSONAL BEST!', title: `Your best score today is ${Number(result.score).toLocaleString()}.`, body: 'Your score is on today’s leaderboard.' };
    }

    return { state: 'accepted', eyebrow: 'RUN SAVED', title: 'Your score has been added to today’s leaderboard.', body: result.rank ? `You’re #${result.rank.rank} today.` : '' };
}

export function formatHud({ score = 0, distance = 0, shield = 'empty' } = {}) {
    return {
        score: Math.max(0, Math.floor(score)).toLocaleString(),
        distance: `${Math.max(0, Math.floor(distance)).toLocaleString()}m`,
        shield: ['ready', 'broken'].includes(shield) ? shield : 'empty',
    };
}
