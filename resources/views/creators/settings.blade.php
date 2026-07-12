<x-app-layout>
    <x-slot name="title">Creator Settings</x-slot>

    <x-slot name="header">
        @include('creators.partials.header', ['section' => 'Settings'])
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @include('creators.partials.navigation')

            @if (session('success'))
                <div class="mt-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-800 ring-1 ring-green-200 dark:bg-emerald-950 dark:text-emerald-200 dark:ring-emerald-900">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('creators.settings.update', $creator) }}" enctype="multipart/form-data" class="mt-6 space-y-8 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-slate-900 dark:ring-slate-800">
                @csrf
                @method('PATCH')

                <section>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-50">Branding</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-slate-300">Customize the imagery shown on your public journey and discovery cards.</p>
                    </div>

                    <div class="mt-6 grid gap-6 md:grid-cols-2">
                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-slate-800">
                            <x-input-label for="avatar" value="Avatar" />

                            <div class="mt-3 flex items-center gap-4">
                                <x-creator-avatar :creator="$creator" size="lg" class="ring-1 ring-slate-200 dark:ring-slate-700" />
                                <p class="text-sm leading-6 text-gray-600 dark:text-slate-300">
                                    Recommended: square image, at least 512x512. JPG, PNG, or WebP. Max 2 MB.
                                </p>
                            </div>

                            <input id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="mt-4 block w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-slate-300 dark:file:bg-indigo-950 dark:file:text-indigo-300">
                            <x-input-error :messages="$errors->get('avatar')" class="mt-2" />

                            @if ($creator->avatar_path)
                                <label class="mt-4 flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-slate-300">
                                    <input type="hidden" name="remove_avatar" value="0">
                                    <input type="checkbox" name="remove_avatar" value="1" @checked(old('remove_avatar')) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                                    Remove current avatar
                                </label>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-slate-800">
                            <x-input-label for="hero" value="Hero image" />

                            <x-creator-hero-background :creator="$creator" class="mt-3 h-28 rounded-xl ring-1 ring-slate-200 dark:ring-slate-700" />
                            <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-slate-300">
                                Recommended: wide image, around 1600x500 or larger. JPG, PNG, or WebP. Max 5 MB.
                            </p>

                            <input id="hero" name="hero" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="mt-4 block w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-slate-300 dark:file:bg-indigo-950 dark:file:text-indigo-300">
                            <x-input-error :messages="$errors->get('hero')" class="mt-2" />

                            @if ($creator->hero_path)
                                <label class="mt-4 flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-slate-300">
                                    <input type="hidden" name="remove_hero" value="0">
                                    <input type="checkbox" name="remove_hero" value="1" @checked(old('remove_hero')) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                                    Remove current hero image
                                </label>
                            @endif
                        </div>
                    </div>
                </section>

                <div class="border-t border-gray-200 pt-8 dark:border-slate-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-50">Creator details</h3>
                </div>

                <div>
                    <x-input-label for="display_name" value="Display name" />
                    <x-text-input id="display_name" name="display_name" class="mt-1 block w-full" :value="old('display_name', $creator->display_name)" required />
                    <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="slug" value="Page slug" />
                    <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug', $creator->slug)" required />
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="youtube_channel_url" value="YouTube channel URL" />
                    <x-text-input id="youtube_channel_url" name="youtube_channel_url" type="url" class="mt-1 block w-full" :value="old('youtube_channel_url', $creator->youtube_channel_url ?: $creator->channel_url)" />
                    <x-input-error :messages="$errors->get('youtube_channel_url')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="bio" value="Bio" />
                    <textarea id="bio" name="bio" rows="5" maxlength="2000" class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500">{{ old('bio', $creator->bio) }}</textarea>
                    <x-input-error :messages="$errors->get('bio')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="submission_instructions" value="Submission instructions" />
                    <textarea id="submission_instructions" name="submission_instructions" rows="5" maxlength="2000" class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500">{{ old('submission_instructions', $creator->submission_instructions) }}</textarea>
                    <x-input-error :messages="$errors->get('submission_instructions')" class="mt-2" />
                </div>

                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-slate-300">
                    <input type="hidden" name="submissions_open" value="0">
                    <input type="checkbox" name="submissions_open" value="1" @checked(old('submissions_open', $creator->submissions_open)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                    Accept new recommendations
                </label>

                <fieldset class="border-t border-gray-200 pt-8 dark:border-slate-800">
                    <legend class="text-lg font-semibold text-gray-900 dark:text-slate-50">Review suggestions before they appear</legend>
                    <p class="mt-1 text-sm text-gray-600 dark:text-slate-300">Choose whether new suggestions need your approval.</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-gray-200 p-4 transition hover:border-indigo-300 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/70 dark:border-slate-700 dark:hover:border-indigo-500/70 dark:has-[:checked]:border-indigo-400 dark:has-[:checked]:bg-indigo-950/40">
                            <input
                                type="radio"
                                name="recommendation_approval_mode"
                                value="manual"
                                @checked(old('recommendation_approval_mode', $creator->recommendation_approval_mode) === 'manual')
                                class="mt-1 border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950"
                            >
                            <span>
                                <span class="block text-sm font-semibold text-gray-900 dark:text-slate-100">On — review first</span>
                                <span class="mt-1 block text-sm leading-6 text-gray-600 dark:text-slate-300">New suggestions remain Pending Review until you approve them.</span>
                            </span>
                        </label>

                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-gray-200 p-4 transition hover:border-indigo-300 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/70 dark:border-slate-700 dark:hover:border-indigo-500/70 dark:has-[:checked]:border-indigo-400 dark:has-[:checked]:bg-indigo-950/40">
                            <input
                                type="radio"
                                name="recommendation_approval_mode"
                                value="auto"
                                @checked(old('recommendation_approval_mode', $creator->recommendation_approval_mode) === 'auto')
                                class="mt-1 border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950"
                            >
                            <span>
                                <span class="block text-sm font-semibold text-gray-900 dark:text-slate-100">Off — appear immediately</span>
                                <span class="mt-1 block text-sm leading-6 text-gray-600 dark:text-slate-300">New suggestions appear publicly right away. You can still hide, pass, or delete them later.</span>
                            </span>
                        </label>
                    </div>

                    <x-input-error :messages="$errors->get('recommendation_approval_mode')" class="mt-2" />
                </fieldset>

                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save settings</button>
            </form>

            <div class="mt-8 rounded-lg border border-red-200 bg-red-50 p-6 dark:border-red-900 dark:bg-red-950/30">
                <h3 class="font-semibold text-red-900 dark:text-red-200">Danger Zone</h3>
                <p class="mt-2 text-sm text-red-700 dark:text-red-300">The public page will become unavailable, but its data will remain intact.</p>
                <form method="POST" action="{{ route('creators.deactivate', $creator) }}" class="mt-4" onsubmit="return confirm('This will hide your creator page and recommendation queue from the public. Existing data will remain stored and may be restored later.')">
                    @csrf
                    @method('PATCH')
                    <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">Deactivate creator page</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
