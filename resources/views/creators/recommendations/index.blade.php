<x-app-layout>
    <x-slot name="title">Manage Recommendations</x-slot>

    <x-slot name="header">
        @include('creators.partials.header', ['section' => 'Manage Recommendations'])
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-[96rem] px-4 sm:px-6 lg:px-8">
            @include('creators.partials.navigation')

            @if (session('success'))
                <div class="mt-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-800 ring-1 ring-green-200 dark:bg-emerald-950 dark:text-emerald-200 dark:ring-emerald-900">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-6 rounded-md bg-red-50 p-4 text-sm text-red-700 ring-1 ring-red-200 dark:bg-red-950 dark:text-red-200 dark:ring-red-900">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="GET" action="{{ route('creators.recommendations.index', $creator) }}" class="mt-6 grid gap-4 rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200 dark:bg-slate-900 dark:ring-slate-800 md:grid-cols-2 xl:grid-cols-[minmax(18rem,1fr)_11rem_11rem_11rem_11rem_auto]">
                <div>
                    <label for="q" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Search</label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Title, artist, channel, or YouTube URL"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                    >
                </div>

                <div>
                    <label for="status-filter" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Status</label>
                    <select id="status-filter" name="status" class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                {{ \App\Models\Recommendation::STATUS_LABELS[$status] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="category-filter" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Category</label>
                    <select id="category-filter" name="category" class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>
                                {{ ucfirst($category) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tag-filter" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Tag</label>
                    <select id="tag-filter" name="tag" class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <option value="">All tags</option>
                        @foreach ($tagOptions as $tag)
                            <option value="{{ $tag->slug }}" @selected(($filters['tag'] ?? '') === $tag->slug)>{{ $tag->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Sort</label>
                    <select id="sort" name="sort" class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest</option>
                        <option value="votes" @selected(($filters['sort'] ?? '') === 'votes')>Most votes</option>
                        <option value="status" @selected(($filters['sort'] ?? '') === 'status')>Status</option>
                        <option value="scheduled" @selected(($filters['sort'] ?? '') === 'scheduled')>Scheduled date</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Apply</button>
                    <a href="{{ route('creators.recommendations.index', $creator) }}" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-600 dark:hover:bg-slate-800">Reset</a>
                </div>
            </form>

            <div class="mt-6 overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-slate-800">
                        <thead class="bg-gray-50 dark:bg-slate-800/80">
                            <tr>
                                @foreach (['Title', 'Artist/channel', 'Category', 'Tags', 'Status', 'Votes', 'Submitted by', 'Submitted date', 'YouTube link', 'Actions'] as $heading)
                                    <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-slate-300">
                                        {{ $heading }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                            @forelse ($recommendations as $recommendation)
                                <tr class="align-top">
                                    <td class="min-w-80 px-4 py-4">
                                        <div class="flex items-start gap-3">
                                            @if ($recommendation->youtubeThumbnailUrl())
                                                <span class="relative block h-12 w-20 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100 sm:h-14 sm:w-24 dark:border-slate-700 dark:bg-slate-950">
                                                    <img
                                                        src="{{ $recommendation->youtubeThumbnailUrl() }}"
                                                        alt="Thumbnail for {{ $recommendation->title }}"
                                                        loading="lazy"
                                                        class="h-full w-full object-cover"
                                                        onerror="this.parentElement.remove()"
                                                    >
                                                    <span class="pointer-events-none absolute inset-0 flex items-center justify-center" aria-hidden="true">
                                                        <span class="flex size-7 items-center justify-center rounded-full bg-black/60 text-white shadow-sm">
                                                            <svg viewBox="0 0 24 24" class="ml-0.5 size-3.5 fill-current"><path d="M8 5v14l11-7z"/></svg>
                                                        </span>
                                                    </span>
                                                </span>
                                            @endif

                                            <div class="min-w-0">
                                                <p class="font-semibold leading-5 text-gray-900 dark:text-slate-50">{{ $recommendation->title }}</p>
                                                @if ($recommendation->isCreatorAdded())
                                                    <span class="mt-1 inline-flex rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-bold text-violet-700 dark:bg-violet-950 dark:text-violet-300">Creator-added</span>
                                                @endif
                                                @if ($recommendation->scheduled_for)
                                                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Scheduled {{ $recommendation->scheduled_for->format('M j, Y g:i A') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="min-w-44 px-4 py-4 text-gray-600 dark:text-slate-300">
                                        {{ $recommendation->channel_title ?: ($recommendation->artist ?: '—') }}
                                    </td>
                                    <td class="px-4 py-4">
                                        @if ($recommendation->category)
                                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium capitalize text-gray-700 dark:bg-slate-800 dark:text-slate-200">{{ $recommendation->category }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="min-w-44 px-4 py-4">
                                        @if ($recommendation->creatorTags->isNotEmpty())
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach ($recommendation->creatorTags as $tag)
                                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $tag->name }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="min-w-60 px-4 py-4">
                                        <form
                                            method="POST"
                                            action="{{ route('creators.recommendations.status', [$creator, $recommendation]) }}"
                                            class="space-y-2"
                                            x-data="{ status: '{{ in_array($recommendation->status, $statuses, true) ? $recommendation->status : 'coming_soon' }}' }"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <select name="status" x-model="status" class="block w-full rounded-md border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                                @foreach ($statuses as $status)
                                                    <option value="{{ $status }}">{{ \App\Models\Recommendation::STATUS_LABELS[$status] }}</option>
                                                @endforeach
                                            </select>

                                            <div x-show="status === 'scheduled'" x-cloak>
                                                <label for="scheduled-for-{{ $recommendation->id }}" class="sr-only">Scheduled date</label>
                                                <input
                                                    id="scheduled-for-{{ $recommendation->id }}"
                                                    name="scheduled_for"
                                                    type="datetime-local"
                                                    value="{{ $recommendation->scheduled_for?->format('Y-m-d\TH:i') }}"
                                                    class="block w-full rounded-md border-gray-300 bg-white text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                                >
                                            </div>

                                            <div class="space-y-2" x-show="status === 'published'" x-cloak>
                                                <label for="reaction-url-{{ $recommendation->id }}" class="sr-only">Published content URL</label>
                                                <input
                                                    id="reaction-url-{{ $recommendation->id }}"
                                                    name="published_reaction_url"
                                                    type="url"
                                                    value="{{ $recommendation->published_reaction_url }}"
                                                    placeholder="Published content URL"
                                                    class="block w-full rounded-md border-gray-300 bg-white text-xs text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                                                >
                                                <label for="published-at-{{ $recommendation->id }}" class="sr-only">Published date</label>
                                                <input
                                                    id="published-at-{{ $recommendation->id }}"
                                                    name="published_at"
                                                    type="datetime-local"
                                                    value="{{ $recommendation->published_at?->format('Y-m-d\TH:i') }}"
                                                    class="block w-full rounded-md border-gray-300 bg-white text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                                >
                                            </div>

                                            <button class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">Save status</button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-4 font-semibold text-gray-900 dark:text-slate-50">{{ $recommendation->user_picks_count }}</td>
                                    <td class="min-w-44 px-4 py-4">
                                        @if ($recommendation->isCreatorAdded())
                                            <p class="font-medium text-violet-700 dark:text-violet-300">Creator-added</p>
                                        @else
                                            <p class="font-medium text-gray-800 dark:text-slate-100">{{ $recommendation->submittedBy->name }}</p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">{{ $recommendation->submittedBy->email }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-gray-600 dark:text-slate-300">{{ $recommendation->created_at->format('M j, Y') }}</td>
                                    <td class="px-4 py-4">
                                        @if ($recommendation->youtube_url)
                                            <a href="{{ $recommendation->youtube_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-red-600 hover:text-red-500 dark:text-red-300 dark:hover:text-red-200">
                                                {{ $recommendation->youtube_video_id ? 'Open YouTube' : 'Open link' }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="min-w-40 px-4 py-4">
                                        <div class="space-y-3">
                                            <div x-data="{ editing: false }" @keydown.escape.window="editing = false">
                                                <button type="button" @click="editing = true" class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">Edit</button>

                                                <div x-show="editing" x-cloak>
                                                    <button type="button" @click="editing = false" class="fixed inset-0 z-40 bg-gray-900/40" aria-label="Close edit form"></button>
                                                    <div role="dialog" aria-modal="true" aria-labelledby="edit-title-{{ $recommendation->id }}" class="fixed inset-x-4 top-8 z-50 mx-auto max-h-[calc(100vh-4rem)] max-w-3xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl dark:border dark:border-slate-700 dark:bg-slate-900">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div>
                                                            <h3 id="edit-title-{{ $recommendation->id }}" class="text-lg font-semibold text-gray-900 dark:text-slate-50">Edit recommendation</h3>
                                                            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ $recommendation->title }}</p>
                                                        </div>
                                                        <button type="button" @click="editing = false" class="rounded-md px-2 py-1 text-sm font-semibold text-gray-500 hover:bg-gray-100 hover:text-gray-800 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">Close</button>
                                                    </div>

                                                    <form method="POST" action="{{ route('creators.recommendations.update', [$creator, $recommendation]) }}" class="mt-6 grid gap-4 md:grid-cols-2">
                                                        @csrf
                                                        @method('PATCH')

                                                        <input type="hidden" name="status" value="{{ in_array($recommendation->status, $statuses, true) ? $recommendation->status : 'coming_soon' }}">

                                                        <div class="md:col-span-2">
                                                            <x-input-label :for="'title-'.$recommendation->id" value="Title" />
                                                            <x-text-input :id="'title-'.$recommendation->id" name="title" class="mt-1 block w-full" :value="$recommendation->title" required />
                                                        </div>

                                                        <div>
                                                            <x-input-label :for="'artist-'.$recommendation->id" value="Artist" />
                                                            <x-text-input :id="'artist-'.$recommendation->id" name="artist" class="mt-1 block w-full" :value="$recommendation->artist" />
                                                        </div>

                                                        <div>
                                                            <x-input-label :for="'channel-title-'.$recommendation->id" value="Channel" />
                                                            <x-text-input :id="'channel-title-'.$recommendation->id" name="channel_title" class="mt-1 block w-full" :value="$recommendation->channel_title" />
                                                        </div>

                                                        <div>
                                                            <x-input-label :for="'category-'.$recommendation->id" value="Category" />
                                                            <select id="category-{{ $recommendation->id }}" name="category" class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                                                <option value="">None</option>
                                                                @foreach ($categories as $category)
                                                                    <option value="{{ $category }}" @selected($recommendation->category === $category)>{{ ucfirst($category) }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div>
                                                            <x-input-label :for="'youtube-url-'.$recommendation->id" value="YouTube URL" />
                                                            <x-text-input :id="'youtube-url-'.$recommendation->id" name="youtube_url" type="url" class="mt-1 block w-full" :value="$recommendation->youtube_url" />
                                                        </div>

                                                        <div class="md:col-span-2">
                                                            <x-input-label :for="'tags-'.$recommendation->id" value="Tags" />
                                                            <x-text-input
                                                                :id="'tags-'.$recommendation->id"
                                                                name="tags"
                                                                class="mt-1 block w-full"
                                                                :value="$recommendation->creatorTags->pluck('name')->implode(', ')"
                                                                placeholder="OPM, Live Performance, Deep Dive"
                                                            />
                                                            <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Add up to 5 tags. Use commas to separate tags. Existing tags are reused automatically.</p>
                                                            @if ($tagOptions->isNotEmpty())
                                                                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Available: {{ $tagOptions->pluck('name')->implode(', ') }}</p>
                                                            @endif
                                                        </div>

                                                        <div>
                                                            <x-input-label :for="'scheduled-edit-'.$recommendation->id" value="Scheduled for" />
                                                            <x-text-input :id="'scheduled-edit-'.$recommendation->id" name="scheduled_for" type="datetime-local" class="mt-1 block w-full" :value="$recommendation->scheduled_for?->format('Y-m-d\TH:i')" />
                                                        </div>

                                                        <div>
                                                            <x-input-label :for="'published-edit-'.$recommendation->id" value="Published at" />
                                                            <x-text-input :id="'published-edit-'.$recommendation->id" name="published_at" type="datetime-local" class="mt-1 block w-full" :value="$recommendation->published_at?->format('Y-m-d\TH:i')" />
                                                        </div>

                                                        <div class="md:col-span-2">
                                                            <x-input-label :for="'reaction-edit-'.$recommendation->id" value="Published content URL" />
                                                            <x-text-input :id="'reaction-edit-'.$recommendation->id" name="published_reaction_url" type="url" class="mt-1 block w-full" :value="$recommendation->published_reaction_url" />
                                                        </div>

                                                        <div class="md:col-span-2">
                                                            <x-input-label :for="'reason-'.$recommendation->id" value="Recommendation reason / note" />
                                                            <textarea id="reason-{{ $recommendation->id }}" name="reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">{{ $recommendation->reason }}</textarea>
                                                        </div>

                                                        <div class="md:col-span-2">
                                                            <x-input-label :for="'moderation-note-'.$recommendation->id" value="Moderation note" />
                                                            <textarea id="moderation-note-{{ $recommendation->id }}" name="moderation_note" rows="3" class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">{{ $recommendation->moderation_note }}</textarea>
                                                        </div>

                                                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-slate-300">
                                                            <input type="hidden" name="is_pinned" value="0">
                                                            <input type="checkbox" name="is_pinned" value="1" @checked($recommendation->is_pinned) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                                                            Pin recommendation
                                                        </label>

                                                        <div class="md:col-span-2">
                                                            <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-indigo-600 dark:hover:bg-indigo-500">Save changes</button>
                                                        </div>
                                                    </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <form method="POST" action="{{ route('creators.recommendations.hide', [$creator, $recommendation]) }}" class="space-y-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="moderation_reason" aria-label="Hide reason" class="block w-full rounded-md border-gray-300 bg-white py-1.5 text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                                    <option value="creator_hidden">Creator hidden</option>
                                                    <option value="inappropriate">Inappropriate</option>
                                                </select>
                                                <button class="font-semibold text-amber-700 hover:text-amber-600 dark:text-amber-300 dark:hover:text-amber-200">Hide</button>
                                            </form>

                                            <form method="POST" action="{{ route('creators.recommendations.destroy', [$creator, $recommendation]) }}" onsubmit="return confirm('Permanently delete this recommendation? This cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="font-semibold text-red-600 hover:text-red-500 dark:text-red-300 dark:hover:text-red-200">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center">
                                        <h3 class="font-semibold text-gray-900 dark:text-slate-50">No recommendations found</h3>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-slate-300">Try changing the search or filters.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">{{ $recommendations->links() }}</div>
        </div>
    </div>
</x-app-layout>
