import { createGame } from './game';
import { COUNTDOWN_STEPS, formatHud, GameUiStateMachine, submissionPresentation } from './ui-state';

const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

class GameUiController {
    constructor(root, helpers) {
        this.root = root; this.helpers = helpers; this.summary = null; this.returnState = null;
        this.overlay = root.querySelector('[data-game-overlay]'); this.panel = root.querySelector('[data-game-panel]');
        this.eyebrow = root.querySelector('[data-game-eyebrow]'); this.title = root.querySelector('[data-game-title]'); this.body = root.querySelector('[data-game-body]');
        this.readyDetails = root.querySelector('[data-game-ready-details]'); this.stats = root.querySelector('[data-game-stats]'); this.counter = root.querySelector('[data-game-counter]');
        this.hudElement = root.querySelector('[data-game-hud]'); this.live = root.querySelector('[data-game-live]'); this.toastElement = root.querySelector('[data-game-toast]');
        this.actions = [...root.querySelectorAll('[data-game-action]')]; this.toastQueue = []; this.toastActive = false;
        this.machine = new GameUiStateMachine(change => this.render(change));
        this.bindActions(); this.updateMute(); this.hud({ score: 0, distance: 0, shield: 'empty' }); this.render({ state: 'loading', payload: {} });
    }

    transition(state, payload = {}) { return this.machine.transition(state, payload); }
    beginLoading() { if (this.machine.state !== 'loading') this.transition('loading'); else this.render({ state: 'loading', payload: {} }); }
    preparationFailed(error) { this.transition('preparation_failed', { error }); }
    ready() { this.hud({ score: 0, distance: 0, shield: 'empty' }); this.transition('ready'); this.announce('Run ready'); }

    async countdown() {
        this.transition('countdown');
        for (const value of COUNTDOWN_STEPS) {
            this.counter.textContent = value; this.helpers.tone(value === 'GO!' ? 760 : 430, .06);
            await sleep(this.helpers.config.countdownStepMs);
        }
    }

    playing(resumed = false) { this.transition('playing'); this.announce(resumed ? 'Run resumed' : 'Run started'); }
    paused() { this.transition('paused'); this.announce('Paused'); }
    startFailed(error) { this.transition('preparation_failed', { error }); }
    gameOver(summary) { this.summary = summary; this.transition('game_over', { summary }); this.announce('Run complete'); }
    submitting(summary) { this.transition('submitting', { summary }); }
    submitted(summary, result, error = null) { const view = submissionPresentation(result, error && !error.payload ? error : null); this.transition(view.state, { summary, result, view }); this.announce(view.eyebrow); }

    hud(values) {
        const formatted = formatHud(values);
        this.root.querySelector('[data-hud-score]').textContent = formatted.score;
        this.root.querySelector('[data-hud-distance]').textContent = formatted.distance;
        if (values.shield !== undefined) this.shield(formatted.shield, false);
    }

    shield(state, announce = true) {
        const shield = this.root.querySelector('[data-hud-shield]');
        shield.dataset.state = state;
        shield.querySelector('[data-shield-value]').textContent = state === 'ready' ? 'READY' : state === 'broken' ? 'BROKEN' : 'EMPTY';
        if (announce && state === 'ready') this.announce('Shield acquired');
        if (announce && state === 'broken') {
            this.announce('Shield consumed');
            clearTimeout(this.shieldTimer);
            this.shieldTimer = setTimeout(() => this.shield('empty', false), this.helpers.config.shieldBrokenMs);
        }
    }

    toggleMute() { this.helpers.setMuted(!this.helpers.isMuted()); this.updateMute(); this.announce(this.helpers.isMuted() ? 'Sound muted' : 'Sound on'); }
    updateMute() { const button = this.root.querySelector('[data-game-mute]'); const muted = this.helpers.isMuted(); button.dataset.muted = muted ? '1' : '0'; button.setAttribute('aria-label', muted ? 'Turn sound on (M)' : 'Mute sound (M)'); button.querySelector('[data-sound-label]').textContent = muted ? 'OFF' : 'ON'; }

    toast(message, tone = 'default', announce = false) {
        if (this.toastQueue.length >= this.helpers.config.toastQueueLimit) this.toastQueue.shift();
        this.toastQueue.push({ message, tone, announce }); this.showNextToast();
    }

    async showNextToast() {
        if (this.toastActive || !this.toastQueue.length) return;
        this.toastActive = true; const toast = this.toastQueue.shift();
        this.toastElement.textContent = toast.message; this.toastElement.dataset.tone = toast.tone; this.toastElement.hidden = false;
        if (toast.announce) this.announce(toast.message);
        await sleep(this.helpers.config.toastDurationMs); this.toastElement.hidden = true; this.toastActive = false; this.showNextToast();
    }

    requestExit() {
        const state = this.machine.state;
        if (state === 'playing') { window.dispatchEvent(new Event('daily-journey-pause')); this.returnState = 'paused'; this.transition('exit_confirm'); return; }
        if (state === 'paused' || state === 'ready') { this.returnState = state; this.transition('exit_confirm'); return; }
        this.helpers.exit();
    }

    announce(message) { this.live.textContent = ''; requestAnimationFrame(() => { this.live.textContent = message; }); }

    bindActions() {
        this.actions.forEach(button => button.addEventListener('click', () => {
            const action = button.dataset.gameAction;
            if (action === 'start') window.dispatchEvent(new Event('daily-journey-start'));
            if (action === 'resume') window.dispatchEvent(new Event('daily-journey-resume'));
            if (action === 'pause') window.dispatchEvent(new Event('daily-journey-pause'));
            if (action === 'retry-preparation' || action === 'play-again') this.helpers.restart();
            if (action === 'restart-request') this.transition('restart_confirm');
            if (action === 'restart-confirm') this.helpers.restart();
            if (action === 'exit-request') this.requestExit();
            if (action === 'exit-confirm') this.helpers.exit();
            if (action === 'keep-playing') { const target = this.returnState || 'paused'; this.transition(target); if (target === 'playing') window.dispatchEvent(new Event('daily-journey-resume')); }
        }));
    }

    render({ state, payload = {} }) {
        this.root.dataset.gameState = state;
        const showOverlay = state !== 'playing';
        this.overlay.hidden = !showOverlay; this.panel.hidden = ['countdown'].includes(state); this.counter.hidden = state !== 'countdown';
        this.hudElement.hidden = ['loading', 'preparation_failed', 'exited'].includes(state);
        this.root.querySelector('[data-game-pause]').disabled = state !== 'playing';
        this.root.querySelector('[data-game-close]').disabled = state === 'submitting';
        this.root.querySelector('[data-game-touch-controls]').hidden = state !== 'playing';
        this.readyDetails.hidden = state !== 'ready'; this.stats.hidden = !['game_over', 'submitting', 'accepted', 'suspicious', 'rejected', 'submission_failed'].includes(state);
        this.actions.forEach(action => { action.hidden = true; action.disabled = state === 'submitting'; });
        this.root.querySelector('[data-game-spinner]').hidden = !['loading', 'submitting'].includes(state);
        this.root.querySelector('[data-result-leaderboard]').hidden = !['accepted', 'suspicious', 'rejected', 'submission_failed'].includes(state);

        const set = (eyebrow, title, body, actions = []) => {
            this.eyebrow.textContent = eyebrow; this.title.textContent = title; this.body.textContent = body;
            actions.forEach(name => { const button = this.root.querySelector(`[data-game-action="${name}"]`); if (button) button.hidden = false; });
        };

        if (state === 'loading') set('PREPARING YOUR RUN', 'Creating today’s trail…', '', []);
        if (state === 'preparation_failed') set('WE COULDN’T START YOUR RUN', 'Please check your connection and try again.', payload.error?.message || '', ['retry-preparation', 'exit-confirm']);
        if (state === 'ready') set('READY FOR TODAY’S TRAIL?', 'Jump, duck, and dodge your way to today’s high score.', 'Collect stars. A shield protects you from one hit.', ['start', 'exit-request']);
        if (state === 'paused') set('PAUSED', 'Your run is safe while paused.', 'P or Escape to resume.', ['resume', 'restart-request', 'exit-request']);
        if (state === 'restart_confirm') set('RESTART CURRENT RUN?', 'Your current score will not be submitted.', 'Start again with a fresh trail.', ['restart-confirm', 'keep-playing']);
        if (state === 'exit_confirm') set('EXIT CURRENT RUN?', 'This run will not be submitted.', 'You can keep playing from the same spot.', ['keep-playing', 'exit-confirm']);
        if (state === 'game_over') set('RUN COMPLETE', 'Your trail has ended.', '', []);
        if (state === 'submitting') set('SAVING YOUR RUN', 'Checking today’s score…', 'Your rank will appear after the server accepts it.', []);
        if (['accepted', 'suspicious', 'rejected', 'submission_failed'].includes(state)) set(payload.view.eyebrow, payload.view.title, payload.view.body, ['play-again', 'exit-confirm']);

        if (payload.summary) {
            this.root.querySelector('[data-result-score]').textContent = Number(payload.summary.score).toLocaleString();
            this.root.querySelector('[data-result-distance]').textContent = `${Number(payload.summary.distance).toLocaleString()}m`;
            this.root.querySelector('[data-result-stars]').textContent = Number(payload.summary.collectibles).toLocaleString();
            const rank = payload.result?.rank?.rank;
            this.root.querySelector('[data-result-rank]').textContent = rank ? `#${rank}` : '—';
        }

        if (showOverlay) requestAnimationFrame(() => this.overlay.querySelector('button:not([hidden])')?.focus({ preventScroll: true }));
    }
}

export async function mountDailyJourney(root) {
    const expanded = root.querySelector('[data-game-expanded]'); const parent = root.querySelector('[data-game-parent]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content; const base = root.dataset.issueUrl;
    let game = null; let muted = localStorage.getItem('dailyJourneyMuted') === '1'; let ui = null;

    const post = async (url, body) => {
        let response;
        try { response = await fetch(url, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body) }); }
        catch (cause) { const error = new Error('Please check your connection and try again.'); error.cause = cause; throw error; }
        const data = await response.json().catch(() => ({}));
        if (!response.ok) { const error = new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Could not complete the request.'); error.payload = data; error.status = response.status; throw error; }
        return data;
    };

    const tone = (frequency, duration) => { if (muted) return; const AudioContext = window.AudioContext || window.webkitAudioContext; if (!AudioContext) return; const context = new AudioContext(); const oscillator = context.createOscillator(); const gain = context.createGain(); oscillator.frequency.value = frequency; gain.gain.value = .035; oscillator.connect(gain).connect(context.destination); oscillator.start(); oscillator.stop(context.currentTime + duration); oscillator.onended = () => context.close(); };

    const exit = () => { game?.destroy(true); game = null; ui?.machine.can('exited') && ui.transition('exited'); expanded.hidden = true; document.body.classList.remove('overflow-hidden'); root.querySelector('[data-game-play]').focus(); };
    const launch = async () => {
        game?.destroy(true); game = null; parent.querySelector('canvas')?.remove();
        ui?.beginLoading();
        try {
            const session = await post(base, {});
            ui ||= new GameUiController(root, { config: session.config.ui, tone, isMuted: () => muted, setMuted: value => { muted = value; localStorage.setItem('dailyJourneyMuted', muted ? '1' : '0'); }, restart: launch, exit });
            ui.helpers.config = session.config.ui; ui.beginLoading();
            game = createGame(parent, session, { base, post, tone, personalBest: Number(root.dataset.personalBest || 0), leaderScore: Number(root.dataset.leaderScore || 0), restart: launch, ui });
        } catch (error) {
            ui ||= new GameUiController(root, { config: { countdownStepMs: 700, toastDurationMs: 1400, toastQueueLimit: 3, shieldBrokenMs: 900 }, tone, isMuted: () => muted, setMuted: value => { muted = value; }, restart: launch, exit });
            if (ui.machine.state === 'loading') ui.preparationFailed(error); else ui.render({ state: 'preparation_failed', payload: { error } });
        }
    };

    const dispatch = name => window.dispatchEvent(new Event(name));
    root.querySelector('[data-game-pause]').addEventListener('click', () => dispatch('daily-journey-pause'));
    root.querySelector('[data-game-mute]').addEventListener('click', () => ui?.toggleMute());
    const jump = root.querySelector('[data-game-jump]'); const duck = root.querySelector('[data-game-duck]');
    jump.addEventListener('pointerdown', event => { event.preventDefault(); dispatch('daily-journey-jump'); });
    duck.addEventListener('pointerdown', event => { event.preventDefault(); dispatch('daily-journey-duck-start'); });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(name => duck.addEventListener(name, event => { event.preventDefault(); dispatch('daily-journey-duck-end'); }));
    root.querySelector('[data-game-close]').addEventListener('click', () => ui?.requestExit() || exit());
    root.querySelector('[data-orientation-continue]').addEventListener('click', () => { localStorage.setItem('dailyJourneyPortraitDismissed', '1'); root.querySelector('[data-game-orientation]').hidden = true; });
    const orientation = () => { root.querySelector('[data-game-orientation]').hidden = localStorage.getItem('dailyJourneyPortraitDismissed') === '1' || !matchMedia('(orientation: portrait) and (max-width: 767px)').matches; };
    orientation(); window.addEventListener('orientationchange', orientation);
    let endsAt = new Date(root.querySelector('[data-game-countdown]').dataset.endsAt).getTime(); const resetWarnings = new Set(); let resetRefreshPending = false;
    setInterval(async () => {
        if (ui?.machine.state !== 'playing') return;
        const minutes = Math.ceil((endsAt - Date.now()) / 60000);
        for (const warning of ui.helpers.config.resetWarningMinutes || []) {
            if (minutes <= warning && minutes > 0 && !resetWarnings.has(warning)) { resetWarnings.add(warning); ui.toast(warning === 1 ? 'DAILY RESET IN 1 MINUTE' : `TODAY’S TRAIL ENDS IN ${warning} MINUTES`, 'warning', true); }
        }
        if (minutes <= 0 && !resetWarnings.has(0)) {
            resetWarnings.add(0); ui.toast('A NEW DAILY TRAIL HAS STARTED — THIS RUN STILL COUNTS FOR THE PREVIOUS TRAIL', 'champion', true);
            if (!resetRefreshPending) {
                resetRefreshPending = true;
                try {
                    const response = await fetch(root.dataset.todayUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
                    if (response.ok) {
                        const context = await response.json(); const countdown = root.querySelector('[data-game-countdown]');
                        countdown.dataset.endsAt = context.ends_at; root.dataset.personalBest = context.me?.score || 0; root.dataset.leaderScore = context.leader?.score || 0;
                        endsAt = new Date(context.ends_at).getTime(); resetWarnings.clear();
                    }
                } catch {
                    // The issued run remains authoritative; the next page/run refreshes context.
                } finally { resetRefreshPending = false; }
            }
        }
    }, 15000);
    await launch();
}
