<section
    id="daily-journey"
    data-daily-journey
    data-authenticated="{{ auth()->check() ? '1' : '0' }}"
    data-issue-url="{{ route('game.runs.issue') }}"
    data-today-url="{{ route('game.today') }}"
    data-login-url="{{ route('login', ['redirect' => route('home').'#daily-journey']) }}"
    data-personal-best="{{ (int) data_get($game, 'me.score', 0) }}"
    data-leader-score="{{ (int) data_get($game, 'leader.score', 0) }}"
    class="border-t border-indigo-400/20 bg-gradient-to-br from-indigo-950 via-slate-950 to-sky-950 px-4 py-8 text-white sm:px-6 lg:px-8"
>
    <div class="mx-auto max-w-7xl overflow-hidden rounded-3xl border border-white/10 bg-white/5 shadow-2xl">
        <div class="grid gap-6 p-6 sm:p-8 lg:grid-cols-[1fr_auto] lg:items-center">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[.22em] text-sky-300">{{ config('daily_journey.title') }}</p>
                <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">Run today’s trail. Claim tomorrow’s legend.</h2>
                <p class="mt-2 max-w-2xl text-slate-300">Run, jump, duck, and dodge your way to the top of today’s leaderboard.</p>
                <div class="mt-5 flex flex-wrap gap-3 text-sm">
                    <span class="rounded-full bg-white/10 px-3 py-2">Leader: <strong>{{ data_get($game, 'leader.guide.name', 'No score yet') }}</strong> @if(data_get($game,'leader.score'))· {{ number_format(data_get($game,'leader.score')) }}@endif</span>
                    <span class="rounded-full bg-white/10 px-3 py-2">Your best: <strong>{{ data_get($game,'me.score') ? number_format(data_get($game,'me.score')) : '—' }}</strong></span>
                    <span class="rounded-full bg-white/10 px-3 py-2">Resets in <strong data-game-countdown data-ends-at="{{ $game['day']->ends_at->toIso8601String() }}">--:--:--</strong></span>
                </div>
            </div>
            <div class="flex flex-wrap gap-3 lg:justify-end">
                <button type="button" data-game-play class="min-h-12 rounded-xl bg-amber-400 px-6 py-3 font-extrabold text-slate-950 shadow-lg hover:bg-amber-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">{{ auth()->check() ? 'Play now' : 'Log in to play' }}</button>
                <a href="{{ route('game.leaderboard') }}" class="min-h-12 rounded-xl border border-white/20 px-5 py-3 text-center font-bold hover:bg-white/10">View leaderboard</a>
            </div>
        </div>

        <div data-game-expanded hidden class="border-t border-white/10 bg-slate-950 p-3 sm:p-5">
            <div data-game-viewport class="relative mx-auto aspect-video w-full max-w-6xl overflow-hidden rounded-2xl border border-white/10 bg-sky-950 shadow-inner">
                <div data-game-parent class="absolute inset-0" aria-label="Daily Journey Challenge game canvas"></div>

                <div data-game-hud hidden class="pointer-events-none absolute inset-x-0 top-0 z-20 flex items-start justify-between gap-2 p-2.5 sm:p-4" aria-label="Game status">
                    <div class="flex min-w-0 gap-2">
                        <div class="game-hud-cell min-w-24 sm:min-w-32">
                            <span class="game-hud-label">Score</span>
                            <strong data-hud-score class="game-hud-value">0</strong>
                        </div>
                        <div class="game-hud-cell min-w-24 sm:min-w-32">
                            <span class="game-hud-label">Distance</span>
                            <strong data-hud-distance class="game-hud-value">0m</strong>
                        </div>
                    </div>
                    <div class="pointer-events-auto flex items-stretch gap-2">
                        <div data-hud-shield data-state="empty" class="game-hud-cell game-shield-cell">
                            <svg viewBox="0 0 24 24" aria-hidden="true" class="h-5 w-5 shrink-0 fill-none stroke-current" stroke-width="2"><path d="M12 3 5 6v5c0 4.8 2.8 8.2 7 10 4.2-1.8 7-5.2 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                            <span><span class="game-hud-label">Shield</span><strong data-shield-value class="game-hud-status">EMPTY</strong></span>
                        </div>
                        <button type="button" data-game-pause aria-label="Pause run (P or Escape)" class="game-hud-button"><span aria-hidden="true" class="text-lg">Ⅱ</span><span class="hidden sm:inline">Pause</span></button>
                        <button type="button" data-game-mute aria-label="Mute sound (M)" class="game-hud-button"><svg viewBox="0 0 24 24" aria-hidden="true" class="h-5 w-5 fill-none stroke-current" stroke-width="2"><path d="M5 9v6h4l5 4V5L9 9H5Z"/><path d="M18 9a4 4 0 0 1 0 6"/></svg><span data-sound-label class="hidden sm:inline">ON</span></button>
                    </div>
                </div>

                <div data-game-toast hidden class="game-toast" role="status"></div>

                <div data-game-overlay class="absolute inset-0 z-30 flex items-center justify-center bg-slate-950/20 p-3 sm:p-6">
                    <div data-game-panel class="game-state-panel w-full max-w-xl text-center">
                        <div data-game-spinner class="mx-auto mb-3 h-8 w-8 animate-pulse rounded-full border-4 border-indigo-300/30 border-t-indigo-300 motion-reduce:animate-none" aria-hidden="true"></div>
                        <p data-game-eyebrow class="text-xs font-extrabold uppercase tracking-[.2em] text-sky-300">Preparing your run</p>
                        <h3 data-game-title class="mt-2 text-2xl font-extrabold text-white sm:text-3xl">Creating today’s trail…</h3>
                        <p data-game-body class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-300 sm:text-base"></p>

                        <div data-game-ready-details hidden class="mt-5">
                            <div class="hidden flex-wrap justify-center gap-2 text-sm font-bold sm:flex">
                                <span class="game-control-chip"><kbd>SPACE</kbd><span>or</span><kbd>↑</kbd><span>Jump</span></span>
                                <span class="game-control-chip"><kbd>↓</kbd><span>Duck</span></span>
                            </div>
                            <div class="flex flex-wrap justify-center gap-2 text-sm font-bold sm:hidden">
                                <span class="game-control-chip"><span class="text-indigo-200">TAP</span><span>Jump</span></span>
                                <span class="game-control-chip"><span class="text-sky-200">HOLD</span><span>Duck</span></span>
                            </div>
                            <p class="mt-4 text-xs text-slate-400">Daily scores reset at 12:00 AM Philippine time.</p>
                        </div>

                        <dl data-game-stats hidden class="mx-auto mt-5 grid max-w-md grid-cols-2 gap-2 sm:grid-cols-4">
                            <div class="game-result-cell"><dt>Score</dt><dd data-result-score>0</dd></div>
                            <div class="game-result-cell"><dt>Distance</dt><dd data-result-distance>0m</dd></div>
                            <div class="game-result-cell"><dt>Stars</dt><dd data-result-stars>0</dd></div>
                            <div class="game-result-cell"><dt>Today</dt><dd data-result-rank>—</dd></div>
                        </dl>

                        <div class="mt-5 flex flex-wrap justify-center gap-2">
                            <button data-game-action="start" hidden type="button" class="game-action-primary">Start run</button>
                            <button data-game-action="resume" hidden type="button" class="game-action-primary">Resume</button>
                            <button data-game-action="restart-request" hidden type="button" class="game-action-secondary">Restart run</button>
                            <button data-game-action="restart-confirm" hidden type="button" class="game-action-danger">Restart run</button>
                            <button data-game-action="retry-preparation" hidden type="button" class="game-action-primary">Try again</button>
                            <button data-game-action="play-again" hidden type="button" class="game-action-primary">Play again</button>
                            <button data-game-action="keep-playing" hidden type="button" class="game-action-primary">Keep playing</button>
                            <button data-game-action="exit-request" hidden type="button" class="game-action-secondary">Exit game</button>
                            <button data-game-action="exit-confirm" hidden type="button" class="game-action-danger">Exit run</button>
                            <a data-result-leaderboard hidden href="{{ route('game.leaderboard') }}" class="game-action-secondary">View leaderboard</a>
                        </div>
                    </div>
                    <div data-game-counter hidden class="text-7xl font-black tabular-nums text-white drop-shadow-lg sm:text-8xl" aria-live="assertive">3</div>
                </div>

                <div data-game-orientation hidden class="absolute inset-0 z-50 flex items-center justify-center bg-slate-950/95 p-6 text-center">
                    <div><svg viewBox="0 0 24 24" aria-hidden="true" class="mx-auto h-10 w-10 fill-none stroke-sky-300" stroke-width="2"><rect x="7" y="3" width="10" height="18" rx="2"/><path d="m3 8 2-2 2 2M21 16l-2 2-2-2"/></svg><h3 class="mt-3 text-xl font-extrabold">TURN YOUR DEVICE</h3><p class="mt-2 text-sm text-slate-300">Daily Journey Challenge plays best in landscape.</p><button data-orientation-continue type="button" class="game-action-secondary mt-4">Continue in portrait</button></div>
                </div>
            </div>

            <div data-game-touch-controls hidden class="mt-3 grid grid-cols-2 gap-3 sm:hidden" aria-label="Touch controls">
                <button data-game-jump class="min-h-16 touch-none rounded-2xl bg-indigo-500 text-lg font-extrabold shadow-lg active:bg-indigo-400"><span class="block text-xs uppercase tracking-wider text-indigo-100">Tap</span>Jump</button>
                <button data-game-duck class="min-h-16 touch-none rounded-2xl bg-sky-600 text-lg font-extrabold shadow-lg active:bg-sky-500"><span class="block text-xs uppercase tracking-wider text-sky-100">Hold</span>Duck</button>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/5 p-3">
                <div class="hidden flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-300 sm:flex" aria-label="Keyboard controls">
                    <span class="game-shell-control"><kbd>SPACE</kbd><span>or</span><kbd>↑</kbd><strong>Jump</strong></span>
                    <span class="game-shell-control"><kbd>↓</kbd><strong>Duck</strong></span>
                    <span class="game-shell-control"><kbd>P / ESC</kbd><strong>Pause</strong></span>
                    <span class="game-shell-control"><kbd>M</kbd><strong>Sound</strong></span>
                </div>
                <p class="text-sm text-slate-300 sm:hidden">Tap Start Run, then use the large Jump and Duck controls.</p>
                <button data-game-close type="button" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-white/15 px-3 py-2 font-bold text-slate-300 hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white disabled:cursor-not-allowed disabled:opacity-50"><span aria-hidden="true">↗</span>Exit game</button>
            </div>
            <p data-game-live class="sr-only" role="status" aria-live="polite"></p>
        </div>
    </div>
</section>
