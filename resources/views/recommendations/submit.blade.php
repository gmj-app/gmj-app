<x-public-layout :title="'Submit to '.$creator->display_name.' | '.config('app.name', 'Guide My Journey')">
        <section class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
            <a href="{{ route('creator.queue', $creator) }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                &larr; Back to the journey
            </a>

            <div class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">Make a recommendation for {{ $creator->display_name }}</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    Suggest an idea or YouTube link for something this creator could make, cover, explore, or discover.
                </p>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    {{ $usage['suggestions_remaining'] }} of {{ $usage['suggestions_limit'] }}
                    suggestions remaining for this creator.
                </p>

                @if (! $creator->submissions_open)
                    <div class="mt-6 rounded-md bg-amber-50 p-4 text-sm font-medium text-amber-800 ring-1 ring-amber-200 dark:bg-amber-950 dark:text-amber-200 dark:ring-amber-900">
                        This creator is not accepting new recommendations right now.
                    </div>
                @endif

                @error('limit')
                    <div class="mt-6 rounded-md bg-red-50 p-4 text-sm font-medium text-red-700 ring-1 ring-red-200 dark:bg-red-950 dark:text-red-200 dark:ring-red-900">
                        {{ $message }}
                    </div>
                @enderror

                @error('submissions')
                    <div class="mt-6 rounded-md bg-red-50 p-4 text-sm font-medium text-red-700 ring-1 ring-red-200 dark:bg-red-950 dark:text-red-200 dark:ring-red-900">
                        {{ $message }}
                    </div>
                @enderror

                @error('favorite_confirmation')
                    <div class="mt-6 rounded-md bg-amber-50 p-4 text-sm font-medium text-amber-800 ring-1 ring-amber-200 dark:bg-amber-950 dark:text-amber-200 dark:ring-amber-900">
                        {{ $message }}
                    </div>
                @enderror

                @if (! $usage['is_favorited'] && $usage['reactors_remaining'] === 0)
                    <div class="mt-6 rounded-md bg-red-50 p-4 text-sm font-medium text-red-700 ring-1 ring-red-200 dark:bg-red-950 dark:text-red-200 dark:ring-red-900">
                        You’ve reached your creator favorite limit. Remove a favorite before suggesting something for this journey.
                    </div>
                @endif

                @if ($creator->submissions_open)
                    <form
                        id="recommendation-submit"
                        method="POST"
                        action="{{ route('recommendations.store', $creator) }}"
                        class="mt-8 space-y-6"
                        @if (! $usage['is_favorited'])
                            x-on:submit="
                                if ($el.dataset.participationConfirmed === '1') return;
                                $event.preventDefault();
                                $dispatch('request-participation-confirmation', {
                                    formId: $el.id,
                                    mode: @js($usage['reactors_remaining'] === 0 ? 'limit' : 'confirm'),
                                    title: @js($usage['reactors_remaining'] === 0 ? 'Favorite limit reached' : 'Add creator to your favorites?'),
                                    body: @js($usage['reactors_remaining'] === 0
                                        ? 'You have used all your creator favorite slots. Remove a favorite before suggesting something for this journey.'
                                        : 'Submitting to this journey will add this creator to your favorites and use 1 creator favorite slot.'),
                                    resourceLine: @js("Creator favorites: {$usage['reactors_used']} of {$usage['reactors_limit']} used"),
                                    confirmLabel: @js($usage['reactors_remaining'] === 0 ? '' : 'Continue and submit'),
                                });
                            "
                        @endif
                        x-data="{
                        type: @js(old('recommendation_type', 'youtube')),
                        youtubeUrl: @js(old('youtube_url', '')),
                        title: @js(old('title', '')),
                        channelTitle: @js(old('channel_title', '')),
                        lookupStatus: '',
                        lookupController: null,
                        async fetchYouTubeDetails() {
                            if (this.type !== 'youtube' || !this.youtubeUrl) {
                                return;
                            }

                            if (this.lookupController) {
                                this.lookupController.abort();
                            }

                            this.lookupController = new AbortController();
                            this.lookupStatus = 'Looking up video details...';

                            try {
                                const url = new URL(@js(route('recommendations.youtube-metadata', $creator)));
                                url.searchParams.set('url', this.youtubeUrl);

                                const response = await fetch(url, {
                                    headers: { 'Accept': 'application/json' },
                                    signal: this.lookupController.signal,
                                });

                                const data = await response.json();

                                if (!response.ok) {
                                    throw new Error(data.message || 'Could not load video details.');
                                }

                                if (data.title) {
                                    this.title = data.title;
                                }

                                if (data.channel_title) {
                                    this.channelTitle = data.channel_title;
                                }

                                this.lookupStatus = data.title || data.channel_title
                                    ? 'Video details loaded.'
                                    : 'No video details found. You can enter them manually.';
                            } catch (error) {
                                if (error.name !== 'AbortError') {
                                    this.lookupStatus = error.message || 'Could not load video details. You can enter them manually.';
                                }
                            }
                        },
                    }"
                    >
                        @csrf
                        <input type="hidden" name="confirm_favorite" value="0">

                    <div>
                        <fieldset>
                            <legend class="block text-sm font-medium text-gray-700 dark:text-slate-300">What are you suggesting?</legend>
                            <div class="mt-2 grid grid-cols-2 gap-2 rounded-lg bg-gray-100 p-1 dark:bg-slate-950">
                                <label
                                    class="cursor-pointer rounded-md px-3 py-2 text-center text-sm font-semibold transition"
                                    :class="type === 'youtube' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-gray-200 dark:bg-slate-900 dark:text-indigo-300 dark:ring-slate-700' : 'text-gray-600 hover:text-gray-900 dark:text-slate-400 dark:hover:text-slate-100'"
                                >
                                    <input class="sr-only" type="radio" name="recommendation_type" value="youtube" x-model="type">
                                    YouTube link
                                </label>
                                <label
                                    class="cursor-pointer rounded-md px-3 py-2 text-center text-sm font-semibold transition"
                                    :class="type === 'topic' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-gray-200 dark:bg-slate-900 dark:text-indigo-300 dark:ring-slate-700' : 'text-gray-600 hover:text-gray-900 dark:text-slate-400 dark:hover:text-slate-100'"
                                >
                                    <input class="sr-only" type="radio" name="recommendation_type" value="topic" x-model="type">
                                    Topic
                                </label>
                            </div>
                        </fieldset>
                        @error('recommendation_type')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="type === 'youtube'" x-cloak class="space-y-6">
                        <div>
                            <label for="youtube_url" class="block text-sm font-medium text-gray-700 dark:text-slate-300">YouTube URL</label>
                            <input
                                id="youtube_url"
                                name="youtube_url"
                                type="url"
                                x-model="youtubeUrl"
                                @change.debounce.500ms="fetchYouTubeDetails()"
                                @blur="fetchYouTubeDetails()"
                                :required="type === 'youtube'"
                                autofocus
                                class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <p x-show="lookupStatus" x-text="lookupStatus" class="mt-2 text-sm text-gray-500 dark:text-slate-400"></p>
                            @error('youtube_url')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="channel_title" class="block text-sm font-medium text-gray-700 dark:text-slate-300">YouTube channel</label>
                            <input
                                id="channel_title"
                                name="channel_title"
                                type="text"
                                x-model="channelTitle"
                                maxlength="255"
                                class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            @error('channel_title')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                            <span x-show="type === 'youtube'">Video title</span>
                            <span x-show="type === 'topic'" x-cloak>Topic title</span>
                        </label>
                        <input
                            id="title"
                            name="title"
                            type="text"
                            x-model="title"
                            maxlength="255"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                        @error('title')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="type === 'topic'" x-cloak>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                            maxlength="1000"
                            :required="type === 'topic'"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 placeholder:text-gray-400 shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Describe the idea, theme, question, or rabbit hole you want {{ $creator->display_name }} to explore."
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Category</label>
                        <select
                            id="category"
                            name="category"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Select a category</option>
                            @foreach (['music', 'documentary', 'culture', 'interview', 'other'] as $category)
                                <option value="{{ $category }}" @selected(old('category') === $category)>
                                    {{ ucfirst($category) }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Why should {{ $creator->display_name }} make, cover, or explore this?</label>
                        <textarea
                            id="reason"
                            name="reason"
                            rows="5"
                            maxlength="1000"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"
                        >{{ old('reason') }}</textarea>
                        @error('reason')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            @disabled($usage['suggestions_remaining'] === 0)
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 disabled:cursor-not-allowed disabled:bg-gray-300 dark:disabled:bg-slate-700"
                        >
                            {{ $usage['can_suggest'] ? 'Submit recommendation' : ($usage['reactors_remaining'] === 0 && ! $usage['is_favorited'] ? 'Favorite limit reached' : 'Suggestion limit reached') }}
                        </button>
                    </div>
                    </form>
                @endif
            </div>
        </section>

        <x-participation-confirmation-modal />
</x-public-layout>
