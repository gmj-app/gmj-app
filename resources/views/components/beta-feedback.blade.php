@php
    $feedbackTypes = [
        'Bug',
        'Confusing UX',
        'Missing feature',
        'Content/data issue',
        'Other',
    ];
@endphp

<div
    x-data="{
        open: false,
        sent: false,
        sending: false,
        errors: {},
        form: {
            name: @js(auth()->user()?->name ?? ''),
            email: @js(auth()->user()?->email ?? ''),
            type: 'Bug',
            message: '',
            extra_context: '',
            current_url: '',
            user_agent: '',
            platform: '',
            timezone: '',
            app_environment: @js(config('app.env')),
            viewport_width: '',
            viewport_height: '',
            screen_width: '',
            screen_height: '',
            meta: '',
        },
        captureContext() {
            try {
                const userAgentData = navigator.userAgentData || {};
                const meta = {
                    language: navigator.language || '',
                    languages: navigator.languages || [],
                    devicePixelRatio: window.devicePixelRatio || 1,
                    referrer: document.referrer || '',
                };

                this.form.current_url = window.location.href;
                this.form.user_agent = navigator.userAgent || '';
                this.form.platform = userAgentData.platform || navigator.platform || '';
                this.form.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
                this.form.viewport_width = window.innerWidth || '';
                this.form.viewport_height = window.innerHeight || '';
                this.form.screen_width = window.screen ? window.screen.width : '';
                this.form.screen_height = window.screen ? window.screen.height : '';
                this.form.meta = JSON.stringify(meta);
            } catch (error) {
                this.form.current_url = window.location.href || '';
            }
        },
        openModal() {
            this.captureContext();
            this.sent = false;
            this.errors = {};
            this.open = true;
            this.$nextTick(() => {
                if (this.$refs.message) {
                    this.$refs.message.focus();
                }
            });
        },
        closeModal() {
            this.open = false;
        },
        errorFor(field) {
            return (this.errors[field] && this.errors[field][0]) || '';
        },
        async submit() {
            this.captureContext();
            this.sending = true;
            this.errors = {};

            try {
                const response = await fetch(@js(route('beta-feedback.store')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.content || '',
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await response.json().catch(() => ({}));

                if (response.status === 422) {
                    this.errors = data.errors || {};
                    return;
                }

                if (! response.ok) {
                    this.errors = { form: [data.message || 'Feedback could not be sent. Please try again.'] };
                    return;
                }

                this.sent = true;
                this.form.message = '';
                this.form.extra_context = '';
            } catch (error) {
                this.errors = { form: ['Feedback could not be sent. Please try again.'] };
            } finally {
                this.sending = false;
            }
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
        aria-label="Open testing feedback form"
        class="fixed bottom-4 right-4 z-40 inline-flex min-h-11 items-center justify-center rounded-full bg-amber-400 px-4 py-2 text-sm font-extrabold text-slate-950 shadow-lg shadow-amber-500/25 ring-1 ring-amber-300 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:bg-indigo-400 dark:text-slate-950 dark:shadow-indigo-500/20 dark:ring-indigo-300 sm:bottom-5 sm:right-5"
    >
        Testing Feedback
    </button>

    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
        role="dialog"
        aria-modal="true"
        aria-labelledby="beta-feedback-title"
    >
        <button
            type="button"
            x-show="open"
            x-transition.opacity
            x-on:click="closeModal()"
            class="fixed inset-0 cursor-default bg-slate-950/60 backdrop-blur-[2px]"
            aria-label="Close testing feedback"
        ></button>

        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative z-10 mb-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900 sm:mx-auto sm:w-full sm:max-w-lg"
            x-on:click.stop
        >
            <form method="POST" action="{{ route('beta-feedback.store') }}" x-on:submit.prevent="submit()" class="p-5 sm:p-7">
                @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400">Beta testing</p>
                    <h2 id="beta-feedback-title" class="mt-1 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">
                        Testing Feedback
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Tell us what happened. This form automatically includes the page and browser details so you do not have to.
                    </p>
                </div>

                <button
                    type="button"
                    x-on:click="closeModal()"
                    class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                    aria-label="Close testing feedback"
                >
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <div x-show="sent" x-cloak class="mt-5 rounded-md bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-200 dark:ring-emerald-900">
                Thanks, your feedback was sent.
            </div>

            <template x-if="errorFor('form')">
                <p x-text="errorFor('form')" class="mt-5 rounded-md bg-red-50 p-4 text-sm font-semibold text-red-700 ring-1 ring-red-200 dark:bg-red-950 dark:text-red-200 dark:ring-red-900"></p>
            </template>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="beta-feedback-name" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Name</label>
                    <input
                        id="beta-feedback-name"
                        name="name"
                        type="text"
                        x-model="form.name"
                        maxlength="255"
                        class="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                    >
                    <p x-show="errorFor('name')" x-text="errorFor('name')" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
                </div>

                <div>
                    <label for="beta-feedback-email" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Email</label>
                    <input
                        id="beta-feedback-email"
                        name="email"
                        type="email"
                        x-model="form.email"
                        maxlength="255"
                        class="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                    >
                    <p x-show="errorFor('email')" x-text="errorFor('email')" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
                </div>
            </div>

            <div class="mt-4">
                <label for="beta-feedback-type" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Feedback type</label>
                <select
                    id="beta-feedback-type"
                    name="type"
                    x-model="form.type"
                    class="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                >
                    @foreach ($feedbackTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
                <p x-show="errorFor('type')" x-text="errorFor('type')" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
            </div>

            <div class="mt-4">
                <label for="beta-feedback-message" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">What happened?</label>
                <textarea
                    id="beta-feedback-message"
                    name="message"
                    rows="5"
                    required
                    maxlength="5000"
                    x-model="form.message"
                    x-ref="message"
                    placeholder="What were you trying to do, what happened, and what did you expect?"
                    class="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                ></textarea>
                <p x-show="errorFor('message')" x-text="errorFor('message')" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
            </div>

            <div class="mt-4">
                <label for="beta-feedback-extra-context" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Optional screenshot/link note</label>
                <textarea
                    id="beta-feedback-extra-context"
                    name="extra_context"
                    rows="3"
                    maxlength="5000"
                    x-model="form.extra_context"
                    placeholder="Optional: paste a screenshot link or any extra context."
                    class="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                ></textarea>
                <p x-show="errorFor('extra_context')" x-text="errorFor('extra_context')" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
            </div>

            <input type="hidden" name="current_url" x-model="form.current_url">
            <input type="hidden" name="user_agent" x-model="form.user_agent">
            <input type="hidden" name="platform" x-model="form.platform">
            <input type="hidden" name="timezone" x-model="form.timezone">
            <input type="hidden" name="app_environment" x-model="form.app_environment">
            <input type="hidden" name="viewport_width" x-model="form.viewport_width">
            <input type="hidden" name="viewport_height" x-model="form.viewport_height">
            <input type="hidden" name="screen_width" x-model="form.screen_width">
            <input type="hidden" name="screen_height" x-model="form.screen_height">
            <input type="hidden" name="meta" x-model="form.meta">

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    x-on:click="closeModal()"
                    class="inline-flex min-h-11 items-center justify-center rounded-full border border-slate-300 bg-white px-5 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus-visible:ring-offset-slate-900"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    x-bind:disabled="sending"
                    class="inline-flex min-h-11 items-center justify-center rounded-full bg-indigo-600 px-5 py-2 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-400 dark:focus-visible:ring-offset-slate-900"
                >
                    <span x-show="! sending">Submit feedback</span>
                    <span x-show="sending" x-cloak>Submitting...</span>
                </button>
            </div>
            </form>
        </div>
    </div>
</div>
