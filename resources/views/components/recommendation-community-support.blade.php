@props([
    'recommendation',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/70']) }}>
    <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
        <div class="min-w-0">
            <x-subsection-label>Suggested by</x-subsection-label>
            <x-requests.requester-identity :requester="$recommendation->submittedBy" />
        </div>

        <div class="min-w-0">
            <x-subsection-label>Community support</x-subsection-label>
            <x-requests.supporter-preview :recommendation="$recommendation" />
        </div>
    </div>
</div>
