<x-app-layout>
    <x-slot name="title">My Activity</x-slot>

    <x-slot name="header">
        <div class="mx-auto min-w-0 max-w-5xl">
            <x-page-header eyebrow="My Guide Activity" title="My Activity" subtitle="See what you’ve suggested and where your votes are currently allocated." compact />
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <main class="px-4 sm:px-6 lg:px-8">
            <div class="mx-auto min-w-0 max-w-5xl">
                <nav aria-label="Activity filters" class="flex gap-2 overflow-x-auto pb-1">
                    @foreach (['all' => 'All', 'votes' => 'Votes', 'suggestions' => 'Requests', 'published' => 'Published'] as $value => $label)
                        <a href="{{ route('activity.index', $value === 'all' ? [] : ['type' => $value]) }}" @class([
                            'inline-flex min-h-11 shrink-0 items-center rounded-full border px-4 py-2 text-sm font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500',
                            'border-emerald-500 bg-emerald-500 text-slate-950' => $type === $value,
                            'border-slate-300 bg-white text-slate-700 hover:border-emerald-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200' => $type !== $value,
                        ]) aria-current="{{ $type === $value ? 'page' : 'false' }}">{{ $label }}</a>
                    @endforeach
                </nav>

                @if ($creators->isEmpty())
                    <section class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900">
                        <h2 class="text-lg font-extrabold text-slate-950 dark:text-white">{{ $type === 'all' ? 'You haven’t created any Guide activity yet.' : 'No activity matches this filter.' }}</h2>
                        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600 dark:text-slate-300">Favorite a creator, submit a request, or cast a vote to start building your activity history.</p>
                        <a href="{{ route('home') }}" class="mt-5 inline-flex min-h-11 items-center rounded-xl bg-emerald-500 px-5 py-2 text-sm font-extrabold text-slate-950 hover:bg-emerald-400">Find creators</a>
                    </section>
                @else
                    <section class="mt-6 space-y-3" aria-label="Your creator activity">
                        @foreach ($creators as $creator)
                            <x-dashboard.guide-activity-creator
                                :creator="$creator"
                                :active-votes="$activeVotesByCreator->get($creator->id, collect())"
                                :suggestions="$suggestionsByCreator->get($creator->id, collect())"
                                :default-open="$loop->first"
                            />
                        @endforeach
                    </section>
                @endif
            </div>
        </main>
    </div>
</x-app-layout>
