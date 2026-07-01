<div
    x-data="{
        pendingFormId: '',
        mode: 'confirm',
        title: '',
        body: '',
        resourceLine: '',
        confirmLabel: '',
        submitting: false,
        destructive: false,
        resetState() {
            this.pendingFormId = '';
            this.mode = 'confirm';
            this.title = '';
            this.body = '';
            this.resourceLine = '';
            this.confirmLabel = '';
            this.submitting = false;
            this.destructive = false;
        },
        open(event) {
            const detail = event.detail;

            this.pendingFormId = detail.formId || '';
            this.mode = detail.mode || 'confirm';
            this.title = detail.title || '';
            this.body = detail.body || '';
            this.resourceLine = detail.resourceLine || '';
            this.confirmLabel = detail.confirmLabel || 'Continue';
            this.destructive = detail.destructive || false;
            this.submitting = false;

            this.$dispatch('open-modal', 'participation-confirmation');
        },
        close() {
            this.$dispatch('close-modal', 'participation-confirmation');
            this.resetState();
        },
        continueAction() {
            if (! this.pendingFormId || this.submitting) {
                return;
            }

            const form = document.getElementById(this.pendingFormId);

            if (! form) {
                this.close();
                return;
            }

            const confirmation = form.elements.namedItem('confirm_favorite');

            this.submitting = true;
            form.dataset.participationConfirmed = '1';

            if (confirmation) {
                confirmation.value = '1';
            }

            this.$dispatch('close-modal', 'participation-confirmation');
            form.requestSubmit();
        },
    }"
    x-on:request-participation-confirmation.window="open($event)"
    x-on:modal-closed.window="$event.detail?.name === 'participation-confirmation' ? resetState() : null"
    x-on:reset-modals.window="resetState()"
>
    <x-modal
        name="participation-confirmation"
        max-width="md"
        labelled-by="participation-confirmation-title"
        focusable
    >
        <div class="p-5 sm:p-7">
            <div class="flex items-start gap-4">
                <span
                    class="flex size-12 shrink-0 items-center justify-center rounded-2xl border"
                    :class="mode === 'limit'
                        ? 'border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-300'
                        : destructive
                            ? 'border-rose-200 bg-rose-50 text-rose-600 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-300'
                            : 'border-indigo-100 bg-indigo-50 text-indigo-600 dark:border-indigo-400/20 dark:bg-indigo-500/10 dark:text-indigo-300'"
                >
                    <svg x-show="mode !== 'limit' && ! destructive" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m12 3.75 2.475 5.016 5.535.804-4.005 3.904.946 5.512L12 16.383l-4.951 2.603.946-5.512L3.99 9.57l5.535-.804L12 3.75Z" />
                    </svg>
                    <svg x-show="mode === 'limit'" x-cloak class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.8 2.7 17a2 2 0 0 0 1.7 3h15.2a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0Z" />
                    </svg>
                    <svg x-show="destructive" x-cloak class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12m-10 0 .7 12h6.6L16 7m-7-3h6l1 3H8l1-3Z" />
                    </svg>
                </span>

                <div class="min-w-0">
                    <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400">Guide My Journey</p>
                    <h2 id="participation-confirmation-title" x-text="title" class="mt-1 text-xl font-extrabold tracking-tight text-slate-950 dark:text-slate-50 sm:text-2xl"></h2>
                </div>
            </div>

            <p x-text="body" class="mt-5 text-sm leading-6 text-slate-700 dark:text-slate-300 sm:text-base sm:leading-7"></p>

            <div x-show="resourceLine" class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/70">
                <p x-text="resourceLine" class="text-sm font-bold text-slate-700 dark:text-slate-200"></p>
            </div>

            <p x-show="mode === 'confirm' && ! destructive" class="mt-4 text-sm text-slate-500 dark:text-slate-400">
                You can remove this creator from your favorites later from My Hub.
            </p>

            <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    x-on:click="close()"
                    class="inline-flex min-h-12 w-full items-center justify-center rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus-visible:ring-offset-slate-900 sm:w-auto"
                >
                    Cancel
                </button>

                <a
                    x-show="mode === 'limit'"
                    x-cloak
                    href="{{ route('dashboard') }}"
                    class="inline-flex min-h-12 w-full items-center justify-center rounded-full bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900 sm:w-auto"
                >
                    Open My Hub
                </a>

                <button
                    x-show="mode !== 'limit'"
                    type="button"
                    x-on:click="continueAction()"
                    x-text="confirmLabel"
                    x-bind:disabled="submitting"
                    class="inline-flex min-h-12 w-full items-center justify-center rounded-full px-5 py-3 text-sm font-bold text-white shadow-lg transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900 sm:w-auto"
                    :class="destructive
                        ? 'bg-rose-600 shadow-rose-600/20 hover:bg-rose-500 focus-visible:ring-rose-500'
                        : 'bg-indigo-600 shadow-indigo-600/20 hover:bg-indigo-500 focus-visible:ring-indigo-500'"
                ></button>
            </div>
        </div>
    </x-modal>
</div>
