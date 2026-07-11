<x-public-layout :title="$guide->publicName().' | Guide Profile'">
    <main class="px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <div class="mx-auto min-w-0 max-w-5xl space-y-6">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                <div class="flex flex-col items-start gap-5 sm:flex-row sm:items-center">
                    <x-guide-avatar :user="$guide" size="xl" />
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-600 dark:text-emerald-300">Guide Profile</p>
                        <h1 class="mt-1 break-words text-3xl font-semibold tracking-tight text-slate-950 dark:text-white md:text-4xl">{{ $guide->publicName() }}</h1>
                        <p class="mt-1 text-base font-semibold text-slate-500 dark:text-slate-400">{{ $guide->formattedPublicHandle() }}</p>
                        @if ($guide->primaryGuideAccolade())
                            <p class="mt-3 inline-flex rounded-full bg-amber-100 px-3 py-1.5 text-sm font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                {{ $guide->primaryGuideAccolade()->label }}{{ $guide->guideNumberLabel() ? ' ('.$guide->guideNumberLabel().')' : '' }}
                            </p>
                        @elseif ($guide->guideAccoladeLabel())
                            <p class="mt-3 inline-flex rounded-full bg-amber-100 px-3 py-1.5 text-sm font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300">{{ $guide->guideAccoladeTooltipLine() }}</p>
                        @endif
                        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Joined {{ $guide->created_at->format('F Y') }}</p>
                    </div>
                </div>

                <dl class="mt-7 grid grid-cols-2 gap-3 lg:grid-cols-4">
                    @foreach ([
                        ['label' => Str::plural('suggestion', $stats['suggestions']), 'value' => $stats['suggestions']],
                        ['label' => 'published', 'value' => $stats['published']],
                        ['label' => 'votes cast', 'value' => $stats['votes_cast']],
                        ['label' => 'creators supported', 'value' => $stats['creators_supported']],
                    ] as $stat)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/60">
                            <dd class="text-2xl font-extrabold text-slate-950 dark:text-white">{{ $stat['value'] }}</dd>
                            <dt class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $stat['label'] }}</dt>
                        </div>
                    @endforeach
                </dl>

                @if ($activeSupportCount > 0)
                    <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Currently supporting {{ $activeSupportCount }} active {{ Str::plural('recommendation', $activeSupportCount) }}. Active selections and allocations are private.</p>
                @endif
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-7">
                <x-section-header eyebrow="Guide impact" title="Published from their suggestions" />

                @if ($publishedSuggestions->isEmpty())
                    <p class="mt-5 rounded-2xl bg-slate-50 p-5 text-sm text-slate-600 dark:bg-slate-950/60 dark:text-slate-300">None of this Guide’s suggestions have been published yet.</p>
                @else
                    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($publishedSuggestions as $recommendation)
                            @php
                                $display = $recommendation->publishedDisplayData();
                            @endphp
                            <a href="{{ route('creators.published', $recommendation->creator).'#recommendation-'.$recommendation->id }}" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:border-emerald-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-950 dark:hover:border-emerald-700">
                                <span class="block aspect-video bg-gradient-to-br from-slate-800 to-slate-950">
                                    @if ($display['thumbnail_url'])
                                        <img src="{{ $display['thumbnail_url'] }}" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover transition group-hover:opacity-90">
                                    @endif
                                </span>
                                <span class="block p-4">
                                    <span class="line-clamp-2 block font-bold text-slate-950 group-hover:text-emerald-700 dark:text-white dark:group-hover:text-emerald-300">{{ $display['title'] }}</span>
                                    <span class="mt-2 block text-sm text-slate-500 dark:text-slate-400">{{ $recommendation->creator->display_name }}</span>
                                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">{{ optional($display['date'])->format('M j, Y') }} · {{ $recommendation->totalVotes() }} {{ Str::plural('vote', $recommendation->totalVotes()) }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-7">
                <x-section-header title="Suggestions" />

                @if ($suggestions->isEmpty())
                    <p class="mt-5 rounded-2xl bg-slate-50 p-5 text-sm text-slate-600 dark:bg-slate-950/60 dark:text-slate-300">This Guide hasn’t submitted any public suggestions yet.</p>
                @else
                    <div class="mt-4 divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($suggestions as $recommendation)
                            @php
                                $url = $recommendation->status === 'published'
                                    ? route('creators.published', $recommendation->creator).'#recommendation-'.$recommendation->id
                                    : route('creator.queue', $recommendation->creator).'#recommendation-'.$recommendation->id;
                            @endphp
                            <article class="flex min-w-0 flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <a href="{{ $url }}" class="break-words font-bold text-indigo-700 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">{{ $recommendation->title }}</a>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $recommendation->creator->display_name }} · {{ $recommendation->mediaTypeLabel() }} · {{ $recommendation->created_at->format('M j, Y') }} · {{ $recommendation->totalVotes() }} {{ Str::plural('vote', $recommendation->totalVotes()) }}</p>
                                </div>
                                <span class="w-fit shrink-0 rounded-full px-2.5 py-1 text-xs font-bold {{ $recommendation->statusBadgeClass() }}">{{ $recommendation->statusLabel() }}</span>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-5">{{ $suggestions->links() }}</div>
                @endif
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-7">
                <x-section-header title="Supported recommendations" subtitle="Completed public recommendations this Guide supported. Active support details remain private." />

                @if ($supportedRecommendations->isEmpty())
                    <p class="mt-5 rounded-2xl bg-slate-50 p-5 text-sm text-slate-600 dark:bg-slate-950/60 dark:text-slate-300">This Guide has no public support history yet.</p>
                @else
                    <div class="mt-4 divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($supportedRecommendations as $support)
                            @php
                                $recommendation = $support->recommendation;
                                $url = $recommendation->status === 'published'
                                    ? route('creators.published', $recommendation->creator).'#recommendation-'.$recommendation->id
                                    : route('creator.queue', $recommendation->creator).'#recommendation-'.$recommendation->id;
                            @endphp
                            <article class="flex min-w-0 flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <a href="{{ $url }}" class="break-words font-bold text-indigo-700 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">{{ $recommendation->title }}</a>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $recommendation->creator->display_name }} · {{ $recommendation->statusLabel() }} · {{ $recommendation->totalVotes() }} community {{ Str::plural('vote', $recommendation->totalVotes()) }}</p>
                                </div>
                                <span class="shrink-0 text-sm font-bold text-slate-700 dark:text-slate-200">{{ $support->vote_count }} {{ Str::plural('vote', $support->vote_count) }} contributed</span>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-5">{{ $supportedRecommendations->links() }}</div>
                @endif
            </section>
        </div>
    </main>
</x-public-layout>
