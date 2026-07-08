@props(['user'])

@php
    $promptErrors = $errors->displayNamePrompt;
@endphp

<div
    x-data="{ open: true, dontShowAgain: false }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="display-name-prompt-title"
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" x-on:click="open = false"></div>

    <div
        x-show="open"
        x-transition
        class="relative mx-auto mt-10 w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900"
        x-on:click.stop
    >
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5 dark:border-slate-800">
            <div>
                <h2 id="display-name-prompt-title" class="text-xl font-extrabold tracking-tight text-slate-950 dark:text-white">Choose your public display name</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    You're currently appearing as "Guide." This is the name other fans and creators see when you suggest or support recommendations.
                </p>
            </div>

            <button
                type="button"
                x-on:click="open = false"
                class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                aria-label="Close display name prompt"
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-6 py-5">
            <form method="POST" action="{{ route('profile.display-name.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="prompt_public_display_name" value="Display name" />
                    <x-text-input
                        id="prompt_public_display_name"
                        name="public_display_name"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('public_display_name')"
                        required
                        maxlength="40"
                        autocomplete="nickname"
                        placeholder="e.g. Cher Ree"
                    />
                    <x-input-error class="mt-2" :messages="$promptErrors->get('public_display_name')" />
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <button
                        type="submit"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
                    >
                        Save display name
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('profile.display-name-prompt.dismiss') }}" class="mt-4 space-y-4">
                @csrf

                <label class="flex items-start gap-3 text-sm text-slate-600 dark:text-slate-300">
                    <input
                        type="checkbox"
                        name="dont_show_again"
                        value="1"
                        x-model="dontShowAgain"
                        class="mt-1 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950"
                    >
                    <span>Don't show this again</span>
                </label>

                <button
                    type="submit"
                    x-on:click="if (! dontShowAgain) { $event.preventDefault(); open = false; }"
                    class="inline-flex min-h-10 w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus-visible:ring-offset-slate-900 sm:w-auto"
                >
                    Not now
                </button>
            </form>
        </div>
    </div>
</div>
