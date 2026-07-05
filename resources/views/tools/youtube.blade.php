<x-app-layout>
    <x-slot name="title">YouTube Description Tools</x-slot>

    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Internal Tools</p>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-slate-50">YouTube Descriptions</h2>
        </div>
    </x-slot>

    <div class="py-10 sm:py-12">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-800 dark:border-indigo-900 dark:bg-indigo-950/60 dark:text-indigo-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 dark:border-rose-900 dark:bg-rose-950/60 dark:text-rose-200">
                    Please confirm the bulk update warning and check the form fields.
                </div>
            @endif

            <div class="mb-6">
                <a href="{{ route('tools.admin') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Back to internal tools</a>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Bulk editor</p>
                            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">Preview description updates</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Preview first, then apply only the videos listed in the preview. The tool stores original descriptions before changing YouTube.
                            </p>
                        </div>

                        <a href="{{ route('tools.youtube.connect') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-indigo-500">
                            {{ $token ? 'Reconnect YouTube' : 'Connect YouTube' }}
                        </a>
                    </div>

                    @if (! $enabled)
                        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-200">
                            YouTube API tools are disabled. Set YOUTUBE_API_ENABLED=true before connecting or applying updates.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tools.youtube.preview') }}" class="mt-8 space-y-6">
                        @csrf

                        <div>
                            <label for="append_text" class="block text-sm font-bold text-slate-800 dark:text-slate-100">Text to append</label>
                            <textarea id="append_text" name="append_text" rows="5" class="mt-2 block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">{{ old('append_text') }}</textarea>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="find_text" class="block text-sm font-bold text-slate-800 dark:text-slate-100">Find text</label>
                                <textarea id="find_text" name="find_text" rows="3" class="mt-2 block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">{{ old('find_text') }}</textarea>
                            </div>
                            <div>
                                <label for="replace_text" class="block text-sm font-bold text-slate-800 dark:text-slate-100">Replace text</label>
                                <textarea id="replace_text" name="replace_text" rows="3" class="mt-2 block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">{{ old('replace_text') }}</textarea>
                            </div>
                        </div>

                        <div class="grid gap-3">
                            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-200">
                                <input type="hidden" name="append_only_if_missing" value="0">
                                <input type="checkbox" name="append_only_if_missing" value="1" @checked(old('append_only_if_missing', '1') === '1') class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span>Append only if text is not already present</span>
                            </label>
                            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-200">
                                <input type="hidden" name="add_separator" value="0">
                                <input type="checkbox" name="add_separator" value="1" @checked(old('add_separator', '1') === '1') class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span>Add separator line before appended text</span>
                            </label>
                        </div>

                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-slate-950 px-5 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
                            Preview updates
                        </button>
                    </form>
                </section>

                <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-lg font-bold text-slate-950 dark:text-white">Connection</h2>
                    @if ($token)
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">Channel</dt>
                                <dd class="mt-1 font-bold text-slate-950 dark:text-white">{{ $token->channel_title ?: 'Connected account' }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">Token expires</dt>
                                <dd class="mt-1 text-slate-700 dark:text-slate-200">{{ optional($token->expires_at)->diffForHumans() ?: 'Unknown' }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">No YouTube channel is connected yet.</p>
                    @endif

                    <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-200">
                        Bulk updates cannot easily be undone unless backups are available. This tool stores original descriptions before applying changes.
                    </div>
                </aside>
            </div>

            @if ($preview)
                <section class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Preview</p>
                            <h2 class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">Videos found: {{ $preview->totalVideos() }}</h2>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                {{ $preview->changedVideos()->count() }} would change. {{ $preview->skippedVideos()->count() }} skipped.
                            </p>
                        </div>

                        @if ($preview->changedVideos()->isNotEmpty())
                            <form method="POST" action="{{ route('tools.youtube.apply') }}" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900 dark:bg-rose-950/50">
                                @csrf
                                <label class="flex items-start gap-3 text-sm font-semibold text-rose-900 dark:text-rose-100">
                                    <input type="checkbox" name="confirm_bulk_update" value="1" class="mt-1 rounded border-rose-300 text-rose-600 focus:ring-rose-500">
                                    <span>I understand this will update YouTube video descriptions.</span>
                                </label>
                                <button type="submit" class="mt-4 inline-flex min-h-11 items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-rose-500">
                                    Apply updates
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                        <div class="grid grid-cols-[7rem_minmax(0,1fr)_8rem] bg-slate-100 px-4 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                            <span>Status</span>
                            <span>Video</span>
                            <span>Action</span>
                        </div>
                        <div class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach ($preview->changes as $change)
                                <div class="grid grid-cols-[7rem_minmax(0,1fr)_8rem] gap-3 px-4 py-4 text-sm">
                                    <span class="font-bold {{ $change->changed() ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-500 dark:text-slate-400' }}">{{ $change->changed() ? 'Change' : 'Skipped' }}</span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-bold text-slate-950 dark:text-white">{{ $change->videoTitle }}</span>
                                        @if ($change->message)
                                            <span class="mt-1 block text-slate-500 dark:text-slate-400">{{ $change->message }}</span>
                                        @endif
                                    </span>
                                    <span class="font-semibold text-slate-600 dark:text-slate-300">{{ ucfirst($change->action) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @if ($lastBatchId)
                <p class="mt-4 text-sm font-semibold text-slate-600 dark:text-slate-300">
                    Last operation batch: {{ $lastBatchId }}
                </p>
            @endif
        </div>
    </div>
</x-app-layout>
