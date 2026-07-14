<x-public-layout :title="'Closed Requests | '.$creator->display_name.' | '.config('app.name', 'Guide My Journey')">
    @php $totalClosed = (int) $closedCounts->sum(); @endphp
    <section class="px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <div class="mx-auto min-w-0 max-w-6xl">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <x-creator-hero-background :creator="$creator" class="min-h-40 sm:min-h-36">
                    <div class="relative z-10 flex min-h-40 flex-col justify-center gap-4 px-4 py-5 sm:min-h-36 sm:px-5 lg:flex-row lg:items-center lg:justify-between lg:px-6">
                        <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                            <x-creator-avatar :creator="$creator" size="xl" class="size-16 shrink-0 border-2 border-white/50 shadow-xl ring-4 ring-slate-950/25 sm:size-20 sm:text-2xl" />
                            <div class="min-w-0">
                                <a href="{{ route('creator.queue', $creator) }}" class="text-sm font-bold text-white/85 hover:text-white">{{ $creator->display_name }}</a>
                                <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Closed Requests</h1>
                                <p class="mt-2 max-w-2xl text-sm font-medium text-white/85">Requests this creator has already seen or decided not to pursue.</p>
                            </div>
                        </div>
                        <span class="w-fit rounded-full border border-white/20 bg-white/15 px-3 py-1.5 text-xs font-medium text-white/90 backdrop-blur-sm">{{ $totalClosed }} closed {{ Str::plural('request', $totalClosed) }}</span>
                    </div>
                </x-creator-hero-background>
            </div>

            <nav aria-label="Creator request sections" class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('creator.queue', $creator) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">Active</a>
                <a href="{{ route('creators.published', $creator) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 hover:border-emerald-300 hover:text-emerald-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">Published</a>
                <a href="{{ route('creators.closed', $creator) }}" aria-current="page" class="rounded-xl border border-indigo-500 bg-indigo-50 px-4 py-2 text-sm font-bold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">Closed</a>
            </nav>

            <div class="mt-5 flex flex-wrap gap-2" aria-label="Filter closed requests">
                @foreach (['' => 'All', 'already_seen' => 'Already Seen', 'passed' => 'Passed'] as $value => $label)
                    <a href="{{ route('creators.closed', array_filter(['creator' => $creator, 'status' => $value])) }}" @if($filters['status'] === $value) aria-current="page" @endif class="rounded-full border px-4 py-2 text-sm font-bold transition {{ $filters['status'] === $value ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">{{ $label }}</a>
                @endforeach
            </div>

            <div class="mt-5 space-y-4">
                @forelse ($closedRecommendations as $recommendation)
                    <x-closed-request-card :recommendation="$recommendation" />
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                        <h2 class="text-lg font-bold text-slate-950 dark:text-white">No closed requests here.</h2>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Requests marked Already Seen or Passed will appear in this archive.</p>
                    </div>
                @endforelse
            </div>

            @if ($closedRecommendations->hasPages())
                <div class="mt-6">{{ $closedRecommendations->links() }}</div>
            @endif
        </div>
    </section>
</x-public-layout>
