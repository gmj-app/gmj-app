<x-app-layout>
    <x-slot name="title">Set Up Your Creator Page</x-slot>

    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Creator setup</p>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-slate-50">Set up your creator page</h2>
        </div>
    </x-slot>

    <div class="py-10 sm:py-12">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                <div class="max-w-2xl">
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">Set up your creator page</h1>
                    <p class="mt-3 leading-7 text-slate-600 dark:text-slate-300">
                        Create a public journey page where your community can suggest ideas, share links, and vote on what they want to see next.
                    </p>
                    <p class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-medium leading-6 text-indigo-800 dark:border-indigo-900 dark:bg-indigo-950/50 dark:text-indigo-200">
                        Creator pages are manually created during beta. YouTube verification is coming later.
                    </p>
                </div>

                <form method="POST" action="{{ route('creators.store') }}" class="mt-8 space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="display_name" value="Creator display name" />
                        <x-text-input
                            id="display_name"
                            name="display_name"
                            class="mt-1 block w-full"
                            :value="old('display_name')"
                            placeholder="Russell Reacts"
                            maxlength="255"
                            required
                            autofocus
                        />
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">The name your community will see across your page.</p>
                        <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="slug" value="Page URL" />
                        <div class="mt-1 flex min-w-0 items-center rounded-md border border-gray-300 bg-white shadow-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                            <span class="shrink-0 pl-3 text-sm text-slate-500 dark:text-slate-400">{{ url('/') }}/</span>
                            <input
                                id="slug"
                                name="slug"
                                type="text"
                                value="{{ old('slug') }}"
                                placeholder="russell-reacts"
                                maxlength="100"
                                pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                class="min-w-0 flex-1 rounded-md border-0 bg-transparent px-1 py-2 text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500"
                                required
                            >
                        </div>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">This becomes your public page URL. Use lowercase letters, numbers, and hyphens.</p>
                        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="youtube_channel_url" value="YouTube channel URL" />
                        <x-text-input
                            id="youtube_channel_url"
                            name="youtube_channel_url"
                            type="url"
                            class="mt-1 block w-full"
                            :value="old('youtube_channel_url')"
                            placeholder="https://www.youtube.com/@yourchannel"
                            maxlength="255"
                            required
                        />
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Paste your main YouTube channel URL.</p>
                        <x-input-error :messages="$errors->get('youtube_channel_url')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="bio" value="Bio" />
                        <textarea id="bio" name="bio" rows="4" maxlength="2000" placeholder="Briefly tell fans what your channel is about." class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500">{{ old('bio') }}</textarea>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Briefly tell fans what your channel is about.</p>
                        <x-input-error :messages="$errors->get('bio')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="submission_instructions" value="Submission instructions" />
                        <textarea id="submission_instructions" name="submission_instructions" rows="4" maxlength="2000" placeholder="Tell fans what kinds of ideas, topics, or links you want them to submit." class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500">{{ old('submission_instructions') }}</textarea>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Tell fans what kinds of ideas, topics, or links you want them to submit.</p>
                        <x-input-error :messages="$errors->get('submission_instructions')" class="mt-2" />
                    </div>

                    <fieldset class="border-t border-slate-200 pt-6 dark:border-slate-800">
                        <legend class="text-lg font-semibold text-slate-950 dark:text-white">Recommendation approval</legend>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Choose when new suggestions become public.</p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-indigo-300 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/70 dark:border-slate-700 dark:hover:border-indigo-500/70 dark:has-[:checked]:border-indigo-400 dark:has-[:checked]:bg-indigo-950/40">
                                <input type="radio" name="recommendation_approval_mode" value="manual" @checked(old('recommendation_approval_mode', 'manual') === 'manual') class="mt-1 border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950">
                                <span>
                                    <span class="block text-sm font-semibold text-slate-950 dark:text-white">Hold for review</span>
                                    <span class="mt-1 block text-sm leading-6 text-slate-600 dark:text-slate-300">New suggestions wait for your approval before appearing publicly.</span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-indigo-300 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/70 dark:border-slate-700 dark:hover:border-indigo-500/70 dark:has-[:checked]:border-indigo-400 dark:has-[:checked]:bg-indigo-950/40">
                                <input type="radio" name="recommendation_approval_mode" value="auto" @checked(old('recommendation_approval_mode') === 'auto') class="mt-1 border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950">
                                <span>
                                    <span class="block text-sm font-semibold text-slate-950 dark:text-white">Auto-approve</span>
                                    <span class="mt-1 block text-sm leading-6 text-slate-600 dark:text-slate-300">New suggestions appear publicly right away. You can still hide, pass, or delete them later.</span>
                                </span>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('recommendation_approval_mode')" class="mt-2" />
                    </fieldset>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                        <input type="hidden" name="submissions_open" value="0">
                        <input type="checkbox" name="submissions_open" value="1" @checked(old('submissions_open', true)) class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950">
                        <span>
                            <span class="block text-sm font-semibold text-slate-950 dark:text-white">Accept new suggestions</span>
                            <span class="mt-1 block text-sm leading-6 text-slate-600 dark:text-slate-300">Your community can submit ideas and links as soon as the page is created.</span>
                        </span>
                    </label>
                    <x-input-error :messages="$errors->get('submissions_open')" class="-mt-4" />

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-end dark:border-slate-800">
                        <a href="{{ route('dashboard') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl px-5 py-3 text-sm font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900">
                            Create creator page
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
