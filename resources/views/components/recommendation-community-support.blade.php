@props([
    'recommendation',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/70']) }}>
    <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
        <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Requested by</p>
            @if ($recommendation->submittedBy)
                <div class="mt-2 flex min-w-0 items-center gap-3">
                    <x-recommendation-support-avatars
                        :recommendation="$recommendation"
                        :limit="1"
                        :include-upvoters="false"
                        layout="detail"
                    />
                    <p class="min-w-0 truncate text-sm font-medium text-slate-700 dark:text-slate-200">{{ $recommendation->submittedBy->publicName() }}</p>
                </div>
            @else
                <p class="mt-2 text-sm font-normal text-slate-500 dark:text-slate-400">Unknown Guide</p>
            @endif
        </div>

        <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Community support</p>
            <x-recommendation-support-avatars
                :recommendation="$recommendation"
                :limit="50"
                :include-requester="false"
                :skip-requester-upvote="true"
                :show-empty="true"
                :show-names="true"
                layout="detail"
                class="mt-2"
            />
        </div>
    </div>
</div>
