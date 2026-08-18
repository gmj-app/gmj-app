<x-public-layout :title="'Merged Request | '.config('app.name')" :canonical="route('recommendations.merged',[$creator,$recommendation])">
    <main class="mx-auto max-w-2xl px-4 py-16">
        <section class="rounded-3xl border border-slate-200 bg-white p-7 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <x-requests.status-badge :request="$recommendation" />
            <h1 class="mt-4 text-2xl font-extrabold">{{ $recommendation->displayTitle() }}</h1>
            <p class="mt-4 text-slate-600 dark:text-slate-300">This Request was merged into:</p>
            @if($recommendation->mergedInto)
                <a class="mt-3 inline-flex rounded-xl bg-indigo-600 px-5 py-3 font-bold text-white" href="{{ route('creator.queue',$creator).'#recommendation-'.$recommendation->mergedInto->id }}">{{ $recommendation->mergedInto->displayTitle() }}</a>
            @else
                <p class="mt-3 font-semibold">The canonical Request is temporarily unavailable.</p>
            @endif
        </section>
    </main>
</x-public-layout>
