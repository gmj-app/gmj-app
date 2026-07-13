@props(['summary'])

@php($featured = $summary['featured'])
<section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            @if ($featured)
                <x-accolade-badge :definition="$featured['definition']" size="lg" />
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $featured['definition']['description'] }}</p>
            @else
                <p class="font-bold text-slate-950 dark:text-white">No featured accolade yet</p>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Earn your first milestone to choose an accolade to feature.</p>
            @endif
        </div>

        @if ($summary['feature_options']->isNotEmpty())
            @php($featuredId = (string) ($featured['award']->id ?? ''))
            <form method="POST" action="{{ route('profile.accolades.featured') }}" x-data="{ selected: @js($featuredId), initial: @js($featuredId), submitting: false }" x-on:submit="submitting = true" class="flex w-full flex-col gap-2 sm:flex-row sm:items-end lg:w-auto">
                @csrf @method('PATCH')
                <div class="min-w-0 flex-1 lg:w-64">
                    <label for="private-featured-accolade" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Featured accolade</label>
                    <select id="private-featured-accolade" name="accolade_id" x-model="selected" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                        @foreach ($summary['feature_options'] as $item)<option value="{{ $item['award']->id }}" @selected((string) $item['award']->id === $featuredId)>{{ $item['definition']['name'] }}</option>@endforeach
                    </select>
                </div>
                <button type="submit" x-bind:disabled="submitting || selected === initial" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                    <span x-show="! submitting">Feature</span><span x-show="submitting" x-cloak>Saving...</span>
                </button>
            </form>
        @endif
    </div>
</section>
