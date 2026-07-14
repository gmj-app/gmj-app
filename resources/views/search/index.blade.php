<x-public-layout :title="'Search | '.config('app.name', 'Guide My Journey')">
    <section class="px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <x-page-header eyebrow="Search" title="Search results" :subtitle="$searchable ? 'Results for “'.$query.'”' : null">
                <x-slot:actions>
                <form method="GET" action="{{ route('search.index') }}" class="flex w-full max-w-xl gap-2">
                    <label for="search-query" class="sr-only">Search creators, artists, songs, or topics</label>
                    <input
                        id="search-query"
                        name="q"
                        type="search"
                        value="{{ $query }}"
                        minlength="2"
                        required
                        placeholder="Search creators, artists, songs, or topics..."
                        class="min-w-0 flex-1 rounded-xl border-slate-300 bg-white text-slate-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                    >
                    <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-500">Search</button>
                </form>
                </x-slot:actions>
            </x-page-header>

            @if (! $searchable)
                <div class="mt-10 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                    <h2 class="text-lg font-bold text-slate-950 dark:text-white">Enter at least 2 characters.</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Try a creator name, artist, song, video title, URL, or topic.</p>
                </div>
            @elseif ($creators->isEmpty())
                <div class="mt-10 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                    <h2 class="text-lg font-bold text-slate-950 dark:text-white">No matches found for “{{ $query }}”.</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Try a creator name, artist, song, video title, or topic.</p>
                    <a href="{{ route('home') }}" class="mt-5 inline-flex text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Browse popular creators</a>
                </div>
            @else
                <div class="mt-10 grid gap-5 lg:grid-cols-2">
                    @foreach ($creators as $creator)
                        @php
                            $matches = $matchingRecommendations->get($creator->id, collect());
                            $visibleMatches = $matches->take(3);
                            $hiddenMatches = max(0, $matches->count() - $visibleMatches->count());
                            $creatorUrl = route('creator.queue', ['creator' => $creator, 'q' => $query]);
                        @endphp

                        <article class="flex min-w-0 flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-300 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-700">
                            <a href="{{ $creatorUrl }}" class="group flex min-w-0 items-center gap-4 p-5 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500" aria-label="View {{ $creator->display_name }} search matches">
                                <x-creator-avatar :creator="$creator" size="lg" class="ring-2 ring-slate-200 dark:ring-slate-700" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-lg font-bold text-slate-950 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-300">{{ $creator->display_name }}</span>
                                    <span class="mt-0.5 block truncate text-sm font-medium text-slate-500 dark:text-slate-400">{{ '@'.$creator->slug }}</span>
                                    <span class="mt-2 line-clamp-2 text-sm leading-5 text-slate-600 dark:text-slate-300">{{ $creator->card_description }}</span>
                                </span>
                            </a>

                            <div class="mx-5 border-t border-slate-200 pt-4 dark:border-slate-800">
                                @if ($matches->isEmpty())
                                    <p class="pb-5 text-sm font-semibold text-indigo-600 dark:text-indigo-300">Creator match</p>
                                @else
                                    <x-subsection-label>
                                        {{ $matches->count() }} matching {{ Str::plural('request', $matches->count()) }}
                                    </x-subsection-label>

                                    <div class="mt-3 divide-y divide-slate-200 dark:divide-slate-800">
                                        @foreach ($visibleMatches as $recommendation)
                                            @php
                                                $isPublished = $recommendation->status === 'published';
                                                $recommendationTitle = $isPublished ? $recommendation->displayPublishedTitle() : $recommendation->displayTitle();
                                                $recommendationUrl = match (true) {
                                                    $isPublished => route('creators.published', $creator).'#recommendation-'.$recommendation->id,
                                                    in_array($recommendation->status, \App\Models\Recommendation::CLOSED_PUBLIC_STATUSES, true) => route('creators.closed', $creator).'#recommendation-'.$recommendation->id,
                                                    default => route('creator.queue', ['creator' => $creator, 'q' => $query]).'#recommendation-'.$recommendation->id,
                                                };
                                            @endphp
                                            <a href="{{ $recommendationUrl }}" class="group/match block py-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500" aria-label="Open request {{ $recommendationTitle }} for {{ $creator->display_name }}">
                                                <span class="line-clamp-2 text-sm font-semibold leading-5 text-slate-800 group-hover/match:text-indigo-600 dark:text-slate-100 dark:group-hover/match:text-indigo-300">{{ $recommendationTitle }}</span>
                                                <x-requests.requested-by-you-badge :recommendation="$recommendation" class="mt-1" />
                                                <span class="mt-2 flex flex-wrap items-center gap-2">
                                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $recommendation->statusBadgeClass() }}">{{ $recommendation->statusLabel() }}</span>
                                                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $recommendation->mediaTypeLabel() }}</span>
                                                    @if ($recommendation->displayItemCount() !== null)
                                                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $recommendation->displayItemCount() }} {{ Str::plural('video', $recommendation->displayItemCount()) }}</span>
                                                    @endif
                                                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ (int) $recommendation->user_picks_count }} {{ Str::plural('vote', (int) $recommendation->user_picks_count) }}</span>
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>

                                    @if ($hiddenMatches > 0)
                                        <p class="pb-4 pt-2 text-xs font-bold text-slate-500 dark:text-slate-400">+{{ $hiddenMatches }} more {{ Str::plural('match', $hiddenMatches) }}</p>
                                    @endif
                                @endif
                            </div>

                            <div class="mt-auto border-t border-slate-200 px-5 py-4 text-right dark:border-slate-800">
                                <a href="{{ $creatorUrl }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-500 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-indigo-400">View creator matches</a>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($creators->hasPages())
                    <div class="mt-10">{{ $creators->links() }}</div>
                @endif
            @endif
        </div>
    </section>
</x-public-layout>
