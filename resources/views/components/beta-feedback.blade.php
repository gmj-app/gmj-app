@php
    $betaFeedbackUser = auth()->user();
    $isAdminFeedbackViewer = $betaFeedbackUser?->canViewBetaFeedbackInbox() ?? false;
    $adminFeedbackItems = collect();
    $adminSpamFeedbackItems = collect();
    $adminUnreadFeedbackCount = 0;
    $betaChangelog = app(\App\Services\ChangelogService::class)->get();

    if ($isAdminFeedbackViewer) {
        $adminFeedbackItems = \App\Models\BetaFeedback::query()
            ->notSpam()
            ->with('user')
            ->latest('created_at')
            ->latest('id')
            ->limit(25)
            ->get();
        $adminSpamFeedbackItems = \App\Models\BetaFeedback::query()
            ->spam()
            ->with(['user', 'spamBy'])
            ->latest('spam_at')
            ->limit(25)
            ->get();
        $adminUnreadFeedbackCount = \App\Models\BetaFeedback::query()->unread()->count();
    }

    $feedbackInitials = function (?string $name, ?string $email): string {
        $source = trim((string) ($name ?: $email ?: 'Guest'));
        $parts = preg_split('/\s+/', $source, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) >= 2) {
            return Str::upper(Str::substr($parts[0], 0, 1).Str::substr($parts[1], 0, 1));
        }

        return Str::upper(Str::substr($source, 0, 2));
    };
@endphp

@if ($isAdminFeedbackViewer)
<div
    x-data="{
        open: false,
        activeTab: 'inbox',
        inboxView: 'inbox',
        inboxCount: {{ $adminFeedbackItems->count() }},
        spamCount: {{ $adminSpamFeedbackItems->count() }},
        unreadCount: {{ $adminUnreadFeedbackCount }},
        notice: '',
        markReadUrlTemplate: @js(route('internal.beta-feedback.mark-read', ['feedback' => '__FEEDBACK_ID__'], false)),
        spamUrlTemplate: @js(route('internal.beta-feedback.spam', ['feedback' => '__FEEDBACK_ID__'], false)),
        restoreUrlTemplate: @js(route('internal.beta-feedback.restore', ['feedback' => '__FEEDBACK_ID__'], false)),
        markRead(id, setRead) {
            fetch(this.markReadUrlTemplate.replace('__FEEDBACK_ID__', id), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.content || '',
                },
                body: JSON.stringify({}),
            })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));

                    if (! response.ok) {
                        throw new Error(data.message || 'Feedback could not be marked read.');
                    }

                    setRead(data.read_at || true);
                    this.unreadCount = data.unread_count ?? Math.max(0, this.unreadCount - 1);
                })
                .catch(() => {});
        },
        moderate(url, successMessage, onSuccess) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.content || '',
                },
                body: JSON.stringify({}),
            }).then(async response => {
                const data = await response.json().catch(() => ({}));
                if (! response.ok) throw new Error(data.message || 'Feedback could not be updated.');
                this.unreadCount = data.unread_count ?? this.unreadCount;
                this.notice = data.message || successMessage;
                onSuccess();
                window.setTimeout(() => this.notice = '', 3000);
            }).catch(() => {});
        },
        markSpam(id, wasUnread, hide) {
            if (! window.confirm('Mark as spam?\n\nThis item will be removed from the Feedback Inbox but preserved for review.')) return;
            this.moderate(this.spamUrlTemplate.replace('__FEEDBACK_ID__', id), 'Feedback marked as spam.', () => {
                hide(); this.inboxCount = Math.max(0, this.inboxCount - 1); this.spamCount++;
            });
        },
        restoreSpam(id, hide) {
            this.moderate(this.restoreUrlTemplate.replace('__FEEDBACK_ID__', id), 'Feedback restored to inbox.', () => {
                hide(); this.spamCount = Math.max(0, this.spamCount - 1); this.inboxCount++;
            });
        },
        openModal() {
            this.activeTab = 'inbox';
            this.open = true;
            this.$nextTick(() => {
                if (this.$refs.closeButton) {
                    this.$refs.closeButton.focus();
                }
            });
        },
        closeModal() {
            this.open = false;
        },
    }"
    x-init="$watch('open', value => document.body.classList.toggle('overflow-y-hidden', value))"
    x-on:keydown.escape.window="open ? closeModal() : null"
    x-on:reset-modals.window="open = false"
    x-on:pageshow.window="open = false"
>
    <button
        type="button"
        x-on:click="openModal()"
        aria-label="Open feedback inbox"
        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
    >
        <span>Feedback Inbox</span>
        <span x-show="unreadCount > 0" x-cloak x-text="unreadCount" class="inline-flex min-w-6 items-center justify-center rounded-full bg-slate-950 px-2 py-0.5 text-xs font-black text-white dark:bg-white dark:text-slate-950"></span>
    </button>

    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
        role="dialog"
        aria-modal="true"
        aria-labelledby="beta-feedback-inbox-title"
    >
        <button
            type="button"
            x-show="open"
            x-transition.opacity
            x-on:click="closeModal()"
            class="fixed inset-0 cursor-default bg-slate-950/60 backdrop-blur-[2px]"
            aria-label="Close feedback inbox"
        ></button>

        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative z-10 mb-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900 sm:mx-auto sm:w-full sm:max-w-3xl"
            x-on:click.stop
        >
            <div class="p-5 sm:p-7">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400">Beta testing</p>
                        <h2 id="beta-feedback-inbox-title" class="mt-1 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">
                            Feedback Inbox
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Latest beta feedback from testers.
                        </p>
                    </div>

                    <button
                        type="button"
                        x-ref="closeButton"
                        x-on:click="closeModal()"
                        class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                        aria-label="Close feedback inbox"
                    >
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                        </svg>
                    </button>
                </div>

                <div class="mt-5 flex gap-1 border-b border-slate-200 dark:border-slate-700" role="tablist" aria-label="Feedback inbox sections">
                    <button type="button" id="beta-admin-inbox-tab" role="tab" aria-controls="beta-admin-inbox-panel" x-bind:aria-selected="(activeTab === 'inbox').toString()" x-on:click="activeTab = 'inbox'" x-bind:class="activeTab === 'inbox' ? 'border-indigo-500 text-indigo-700 dark:text-indigo-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'" class="min-h-11 border-b-2 px-4 py-2 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Feedback Inbox</button>
                    <button type="button" id="beta-admin-changelog-tab" role="tab" aria-controls="beta-admin-changelog-panel" x-bind:aria-selected="(activeTab === 'changelog').toString()" x-on:click="activeTab = 'changelog'" x-bind:class="activeTab === 'changelog' ? 'border-indigo-500 text-indigo-700 dark:text-indigo-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'" class="min-h-11 border-b-2 px-4 py-2 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Change Log</button>
                </div>

                <div id="beta-admin-inbox-panel" role="tabpanel" aria-labelledby="beta-admin-inbox-tab" x-show="activeTab === 'inbox'" class="mt-5">
                    <div class="mb-4 flex gap-2" role="tablist" aria-label="Feedback inbox filters">
                        <button type="button" x-on:click="inboxView='inbox'" x-bind:aria-selected="(inboxView==='inbox').toString()" class="rounded-full px-4 py-2 text-sm font-bold" x-bind:class="inboxView==='inbox' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'">Inbox (<span x-text="inboxCount"></span>)</button>
                        <button type="button" x-on:click="inboxView='spam'" x-bind:aria-selected="(inboxView==='spam').toString()" class="rounded-full px-4 py-2 text-sm font-bold" x-bind:class="inboxView==='spam' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'">Spam (<span x-text="spamCount"></span>)</button>
                    </div>
                    <p x-show="notice" x-cloak x-text="notice" role="status" aria-live="polite" class="mb-4 rounded-xl bg-emerald-100 p-3 text-sm font-bold text-emerald-800"></p>
                    <div x-show="inboxView === 'inbox'">
                    <div class="flex items-center justify-between gap-3 border-y border-slate-100 py-3 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                        <span>Showing latest 25</span>
                        <span x-show="unreadCount > 0" x-cloak><strong x-text="unreadCount"></strong> unread</span>
                    </div>

                    @if ($adminFeedbackItems->isNotEmpty())
                    <div class="mt-5 max-h-[62vh] space-y-4 overflow-y-auto pr-1">
                        @foreach ($adminFeedbackItems as $feedback)
                            @php
                                $feedbackAvatarUrl = $feedback->user?->avatar_url;
                                $feedbackName = $feedback->name ?: $feedback->user?->publicName() ?: 'Guest tester';
                                $feedbackEmail = $feedback->email ?: $feedback->user?->email;
                                $feedbackIsRead = filled($feedback->read_at);
                                $feedbackExactDate = $feedback->created_at?->format('M j, Y g:i A');
                            @endphp

                            <article
                                x-data="{ readAt: @js($feedback->read_at?->toIso8601String()), removed: false }"
                                x-show="! removed"
                                x-bind:class="readAt ? 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/70' : 'border-indigo-300 bg-indigo-50/70 ring-1 ring-indigo-200 dark:border-indigo-700 dark:bg-indigo-950/30 dark:ring-indigo-800/60'"
                                class="rounded-2xl border p-4 transition"
                            >
                                <div class="flex items-start gap-3">
                                    <span class="relative mt-0.5 size-10 shrink-0 overflow-hidden rounded-full bg-indigo-100 text-sm font-black text-indigo-700 ring-1 ring-indigo-200 dark:bg-indigo-950 dark:text-indigo-200 dark:ring-indigo-800">
                                        @if ($feedbackAvatarUrl)
                                            <img
                                                src="{{ $feedbackAvatarUrl }}"
                                                alt="{{ $feedbackName }} avatar"
                                                loading="lazy"
                                                onerror="this.hidden = true; this.nextElementSibling.hidden = false"
                                                class="size-full rounded-full object-cover"
                                            >
                                            <span hidden class="flex size-full items-center justify-center">{{ $feedbackInitials($feedbackName, $feedbackEmail) }}</span>
                                        @else
                                            <span class="flex size-full items-center justify-center">{{ $feedbackInitials($feedbackName, $feedbackEmail) }}</span>
                                        @endif
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <h3 class="break-words text-sm font-extrabold text-slate-950 dark:text-white">{{ $feedbackName }}</h3>
                                            @if ($feedbackEmail)
                                                <span class="break-all text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $feedbackEmail }}</span>
                                            @endif
                                            <span x-show="! readAt" x-cloak class="rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-black uppercase tracking-wide text-white">Unread</span>
                                        </div>

                                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                            <span>{{ $feedback->type }}</span>
                                            <time datetime="{{ $feedback->created_at?->toIso8601String() }}" title="{{ $feedbackExactDate }}">
                                                {{ $feedback->created_at?->diffForHumans() }}
                                            </time>
                                        </div>

                                        @if ($feedback->current_url)
                                            <p class="mt-2 break-all text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $feedback->current_url }}</p>
                                        @endif

                                        <p class="mt-3 whitespace-pre-line break-words text-sm leading-6 text-slate-700 dark:text-slate-200">{{ $feedback->message }}</p>

                                        @if ($feedback->extra_context)
                                            <div class="mt-3 rounded-xl border border-slate-200 bg-white/70 p-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-300">
                                                <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Extra context</p>
                                                <p class="mt-1 whitespace-pre-line break-words">{{ $feedback->extra_context }}</p>
                                            </div>
                                        @endif

                                        <div class="mt-4 flex flex-wrap items-center gap-3">
                                            <button
                                                type="button"
                                                x-show="! readAt"
                                                x-on:click="markRead({{ $feedback->id }}, value => readAt = value)"
                                                class="inline-flex min-h-10 items-center justify-center rounded-full bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
                                            >
                                                Mark read
                                            </button>
                                            <span x-show="readAt" class="text-xs font-bold text-slate-500 dark:text-slate-400">
                                                Read
                                            </span>
                                            <button type="button" x-on:click="markSpam({{ $feedback->id }}, ! readAt, () => removed = true)" aria-label="Mark feedback from {{ $feedbackName }} as spam" class="inline-flex min-h-10 items-center justify-center rounded-full border border-red-300 px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/40">Mark as spam</button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    @endif
                    <div x-show="inboxCount === 0" x-cloak class="mt-5 rounded-2xl border border-dashed border-slate-300 px-5 py-10 text-center text-sm font-semibold text-slate-500">No feedback yet.</div>
                    </div>

                    <div x-show="inboxView === 'spam'" x-cloak>
                        <div class="flex items-center justify-between border-y border-slate-100 py-3 text-sm text-slate-500 dark:border-slate-800"><span>Latest 25 spam items</span><span x-text="spamCount + ' retained'"></span></div>
                        @if ($adminSpamFeedbackItems->isNotEmpty())
                            <div class="mt-5 max-h-[62vh] space-y-4 overflow-y-auto pr-1">
                            @foreach($adminSpamFeedbackItems as $feedback)
                                @php($spamName = $feedback->name ?: $feedback->user?->publicName() ?: 'Guest tester')
                                <article x-data="{ removed: false }" x-show="! removed" class="rounded-2xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-900 dark:bg-amber-950/20">
                                    <div class="flex flex-wrap items-center gap-2"><h3 class="font-extrabold">{{ $spamName }}</h3><span class="text-xs text-slate-500">{{ $feedback->email }}</span><span class="rounded-full bg-amber-200 px-2 py-0.5 text-xs font-bold text-amber-900">Spam</span></div>
                                    <div class="mt-1 text-xs text-slate-500">Submitted {{ $feedback->created_at?->format('M j, Y g:i A') }} · Marked {{ $feedback->spam_at?->format('M j, Y g:i A') }} by {{ $feedback->spamBy?->name ?: 'Former admin' }}</div>
                                    <p class="mt-3 whitespace-pre-line break-words text-sm">{{ $feedback->message }}</p>
                                    <button type="button" x-on:click="restoreSpam({{ $feedback->id }}, () => removed = true)" aria-label="Restore feedback from {{ $spamName }} to inbox" class="mt-4 inline-flex min-h-10 items-center rounded-full bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">Restore to inbox</button>
                                </article>
                            @endforeach
                            </div>
                        @endif
                        <div x-show="spamCount === 0" x-cloak class="mt-5 rounded-2xl border border-dashed border-slate-300 px-5 py-10 text-center text-sm font-semibold text-slate-500">No spam feedback.</div>
                    </div>
                </div>

                <div id="beta-admin-changelog-panel" role="tabpanel" aria-labelledby="beta-admin-changelog-tab" x-show="activeTab === 'changelog'" x-cloak class="mt-5">
                    <x-beta-changelog-panel :changelog="$betaChangelog" />
                </div>
            </div>
        </div>
    </div>
</div>
@endif
