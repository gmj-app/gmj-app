<x-public-layout :title="'Submit to '.$creator->display_name.' | '.config('app.name', 'Guide My Journey')">
        <section class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
            <a href="{{ route('creator.queue', $creator) }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                &larr; Back to the journey
            </a>

            <div class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">Submit a request for {{ $creator->display_name }}</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    Suggest an idea or YouTube link for something this creator could make, cover, explore, or discover.
                </p>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    {{ $usage['suggestions_remaining'] }} of {{ $usage['suggestions_limit'] }}
                    requests remaining for this creator.
                </p>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    Submitting this request will use 1 of your request slots for this creator. Voting is separate.
                </p>

                @if (! $creator->submissions_open)
                    <div class="mt-6 rounded-md bg-amber-50 p-4 text-sm font-medium text-amber-800 ring-1 ring-amber-200 dark:bg-amber-950 dark:text-amber-200 dark:ring-amber-900">
                        This creator is not accepting new requests right now.
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

                @if (session('duplicate_recommendation'))
                    @php($duplicateRecommendation = session('duplicate_recommendation'))
                    <div class="mt-6 rounded-2xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900 dark:border-indigo-500/30 dark:bg-indigo-950/40 dark:text-indigo-100">
                        <h2 class="text-base font-extrabold">{{ $duplicateRecommendation['title'] }}</h2>
                        <p class="mt-1 leading-6">{{ $duplicateRecommendation['body'] }}</p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <a href="{{ $duplicateRecommendation['primary_url'] }}" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-indigo-500">
                                {{ $duplicateRecommendation['primary_label'] }}
                            </a>
                            @if ($duplicateRecommendation['secondary_url'])
                                <a href="{{ $duplicateRecommendation['secondary_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50 dark:border-indigo-500/30 dark:bg-slate-950 dark:text-indigo-200 dark:hover:bg-indigo-950/60">
                                    {{ $duplicateRecommendation['secondary_label'] }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-6 rounded-md bg-red-50 p-4 text-sm text-red-700 ring-1 ring-red-200 dark:bg-red-950 dark:text-red-200 dark:ring-red-900">
                        <p class="font-bold">Please fix the highlighted fields and submit again.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (! $usage['is_favorited'] && $usage['reactors_remaining'] === 0)
                    <div class="mt-6 rounded-md bg-red-50 p-4 text-sm font-medium text-red-700 ring-1 ring-red-200 dark:bg-red-950 dark:text-red-200 dark:ring-red-900">
                        You’ve reached your creator favorite limit. Remove a favorite before suggesting something for this journey.
                        <span class="block">Creator favorites: {{ $usage['reactors_used'] }} of {{ $usage['reactors_limit'] }} used.</span>
                    </div>
                @endif

                @if ($creator->submissions_open)
                    <form
                        id="recommendation-submit"
                        method="POST"
                        action="{{ route('recommendations.store', $creator) }}"
                        class="mt-8 space-y-6"
                        x-data="{
                        type: @js(old('recommendation_type', 'youtube')),
                        youtubeUrl: @js(old('youtube_url', '')),
                        title: @js(old('title', '')),
                        channelTitle: @js(old('channel_title', '')),
                        reason: @js(old('reason', '')),
                        lookupStatus: '',
                        lookupController: null,
                        lookupTimer: null,
                        lookupInFlightUrl: '',
                        lookupCompletedUrl: '',
                        lookupFailedUrl: '',
                        lookupMediaType: '',
                        lookupThumbnail: '',
                        lookupItemCount: null,
                        normalizedYouTubeUrl() {
                            return this.youtubeUrl.trim();
                        },
                        hasValidLookingYouTubeUrl(url) {
                            return /^(https?:\/\/)?((www|m)\.)?(youtube\.com\/(playlist\?[^ ]*\blist=[A-Za-z0-9_-]{10,100}\b|watch\?[^ ]*\bv=[A-Za-z0-9_-]{11}\b)|youtube\.com\/(shorts|embed|live)\/[A-Za-z0-9_-]{11}\b|youtu\.be\/[A-Za-z0-9_-]{11}\b)/i.test(url);
                        },
                        scheduleYouTubeDetailsLookup() {
                            clearTimeout(this.lookupTimer);
                            this.lookupTimer = setTimeout(() => this.fetchYouTubeDetails(), 600);
                        },
                        async fetchYouTubeDetails() {
                            const requestedUrl = this.normalizedYouTubeUrl();

                            if (this.type !== 'youtube' || !requestedUrl) {
                                this.lookupStatus = '';
                                this.lookupCompletedUrl = '';
                                this.lookupFailedUrl = '';
                                this.lookupInFlightUrl = '';
                                return;
                            }

                            if (! this.hasValidLookingYouTubeUrl(requestedUrl)) {
                                this.lookupStatus = 'Enter a valid YouTube video or playlist URL.';
                                return;
                            }

                            if (
                                requestedUrl === this.lookupInFlightUrl
                                || requestedUrl === this.lookupCompletedUrl
                                || requestedUrl === this.lookupFailedUrl
                            ) {
                                return;
                            }

                            if (this.lookupController) {
                                this.lookupController.abort();
                            }

                            this.lookupController = new AbortController();
                            this.lookupInFlightUrl = requestedUrl;
                            this.lookupStatus = 'Loading YouTube details...';

                            try {
                                const url = new URL(@js(route('recommendations.youtube-metadata', $creator)));
                                url.searchParams.set('youtube_url', requestedUrl);

                                const response = await fetch(url, {
                                    headers: { 'Accept': 'application/json' },
                                    signal: this.lookupController.signal,
                                });

                                const data = await response.json();

                                if (!response.ok) {
                                    throw new Error(data.message || 'Could not load video details.');
                                }

                                this.lookupCompletedUrl = requestedUrl;
                                this.lookupFailedUrl = '';
                                this.lookupMediaType = data.media_type || 'video';
                                this.lookupThumbnail = data.thumbnail_url || '';
                                this.lookupItemCount = data.item_count ?? null;

                                if (data.title) {
                                    this.title = data.title;
                                }

                                if (data.channel_title) {
                                    this.channelTitle = data.channel_title;
                                }

                                this.lookupStatus = data.message || (data.media_type === 'playlist'
                                    ? 'Playlist details loaded.'
                                    : 'Video details loaded.');
                            } catch (error) {
                                if (error.name !== 'AbortError') {
                                    this.lookupFailedUrl = requestedUrl;
                                    this.lookupStatus = error.message || 'We could not read this YouTube link. Please check the URL or submit it as a topic.';
                                }
                            } finally {
                                if (this.lookupInFlightUrl === requestedUrl) {
                                    this.lookupInFlightUrl = '';
                                }
                            }
                        },
                    }"
                    >
                        @csrf
                        <input
                            type="hidden"
                            name="confirm_favorite"
                            value="{{ ! $usage['is_favorited'] && $usage['reactors_remaining'] > 0 ? '1' : '0' }}"
                        >

                    @if (! $usage['is_favorited'] && $usage['reactors_remaining'] > 0)
                        <div class="rounded-md bg-indigo-50 p-4 text-sm text-indigo-800 ring-1 ring-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-200 dark:ring-indigo-900">
                            Submitting to this journey will add {{ $creator->display_name }} to your favorites and use 1 creator favorite slot.
                            <span class="block font-semibold">Creator favorites: {{ $usage['reactors_used'] }} of {{ $usage['reactors_limit'] }} used.</span>
                        </div>
                    @endif

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
                                @input="scheduleYouTubeDetailsLookup()"
                                @blur="fetchYouTubeDetails()"
                                :required="type === 'youtube'"
                                autofocus
                                class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <p x-show="lookupStatus" x-text="lookupStatus" class="mt-2 text-sm text-gray-500 dark:text-slate-400"></p>
                            <div x-show="lookupMediaType === 'playlist'" class="mt-3 flex items-center gap-3 rounded-xl border border-indigo-200 bg-indigo-50 p-3 dark:border-indigo-500/30 dark:bg-indigo-950/30">
                                <template x-if="lookupThumbnail">
                                    <img :src="lookupThumbnail" alt="" class="h-12 w-[85px] rounded-md object-cover" width="85" height="48">
                                </template>
                                <div class="min-w-0">
                                    <span class="inline-flex rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Playlist</span>
                                    <p x-show="lookupItemCount !== null" class="mt-1 text-xs font-semibold text-indigo-700 dark:text-indigo-200"><span x-text="lookupItemCount"></span> <span x-text="lookupItemCount === 1 ? 'video' : 'videos'"></span></p>
                                </div>
                            </div>
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
                            x-model="reason"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"
                        >{{ old('reason') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                            Optional, up to 1,000 characters.
                            <span class="float-right tabular-nums" x-text="`${reason.length} / 1000`">{{ mb_strlen((string) old('reason', '')) }} / 1000</span>
                        </p>
                        @error('reason')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            form="recommendation-submit"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                        >
                            {{ $usage['can_suggest'] ? 'Submit request' : ($usage['reactors_remaining'] === 0 && ! $usage['is_favorited'] ? 'Favorite limit reached' : 'Request limit reached') }}
                        </button>
                    </div>
                    </form>
                @endif
            </div>
        </section>

</x-public-layout>
