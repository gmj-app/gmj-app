<x-app-layout>
    <x-slot name="title">Edit your request details</x-slot>

    <x-slot name="header">
        <div class="mx-auto max-w-3xl">
            <x-page-header eyebrow="My request" title="Edit your request details" subtitle="You can improve the title and context shown on this request. The linked video, playlist, topic, creator, and request type cannot be changed because other Guides may have voted for it." compact />
        </div>
    </x-slot>

    <main class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl space-y-6">
            @if (session('success'))
                <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-200"><ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Linked request content</p>
                <h2 class="mt-2 break-words text-lg font-bold text-slate-950 dark:text-white">{{ $recommendation->canonicalDisplayTitle() }}</h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="font-semibold text-slate-500">Creator</dt><dd class="mt-1 text-slate-900 dark:text-white">{{ $recommendation->creator->display_name }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">Type</dt><dd class="mt-1 text-slate-900 dark:text-white">{{ $recommendation->mediaTypeLabel() }}</dd></div>
                    @if ($recommendation->canonicalMediaUrl())<div class="sm:col-span-2"><dt class="font-semibold text-slate-500">Original link</dt><dd class="mt-1 break-all text-slate-700 dark:text-slate-200">{{ $recommendation->canonicalMediaUrl() }}</dd></div>@endif
                </dl>
                <p class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300">The creator, linked content, request type, votes, and status cannot be changed here.</p>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                <form method="POST" action="{{ route('requests.presentation.update', $recommendation) }}" class="space-y-5">
                    @csrf @method('PATCH')
                    <div><x-input-label for="display_title_override" value="Display title"/><x-text-input id="display_title_override" name="display_title_override" maxlength="160" class="mt-2 block w-full" :value="old('display_title_override', $recommendation->display_title_override)" placeholder="Optional clearer title"/><p class="mt-1 text-xs text-slate-500">Use a clearer title for this request. The linked content will not change. Leave blank to use the original title. 160 characters maximum.</p></div>
                    <div><x-input-label for="request_context" value="Why this request matters"/><textarea id="request_context" name="request_context" rows="5" maxlength="2000" class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-slate-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old('request_context', $recommendation->request_context) }}</textarea><p class="mt-1 text-xs text-slate-500">Add context that may help the creator understand why you recommended it. 2,000 characters maximum.</p></div>
                    <div class="flex flex-wrap gap-3"><button class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-5 py-2 text-sm font-bold text-white hover:bg-indigo-500">Save presentation</button><a href="{{ route('creator.queue', $recommendation->creator).'#recommendation-'.$recommendation->id }}" class="inline-flex min-h-11 items-center px-2 text-sm font-bold text-slate-600 dark:text-slate-300">Cancel</a></div>
                </form>
            </section>

            <section class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 dark:border-amber-800 dark:bg-amber-950/20 sm:p-6">
                <h2 class="text-lg font-bold text-slate-950 dark:text-white">Request a correction</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Propose a correction for review. This never changes the live request, its votes, or its identity. If replacement is needed, support cannot be transferred.</p>
                <form method="POST" action="{{ route('requests.corrections.store', $recommendation) }}" class="mt-4 space-y-4">
                    @csrf
                    <div><x-input-label for="proposed_url" value="Correct URL (if applicable)"/><x-text-input id="proposed_url" name="proposed_url" type="url" class="mt-2 block w-full" :value="old('proposed_url')"/></div>
                    <div><x-input-label for="proposed_topic" value="Correct topic (if applicable)"/><x-text-input id="proposed_topic" name="proposed_topic" class="mt-2 block w-full" :value="old('proposed_topic')"/></div>
                    <div><x-input-label for="explanation" value="What needs correcting?"/><textarea id="explanation" name="explanation" required maxlength="2000" rows="4" class="mt-2 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">{{ old('explanation') }}</textarea></div>
                    <button class="inline-flex min-h-11 items-center rounded-xl border border-amber-300 bg-white px-4 py-2 text-sm font-bold text-amber-800 hover:bg-amber-100 dark:border-amber-700 dark:bg-slate-900 dark:text-amber-200">Submit correction</button>
                </form>
                @foreach ($recommendation->identityCorrections->where('status', 'pending') as $correction)
                    <div class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-amber-200 bg-white p-3 text-sm dark:border-amber-800 dark:bg-slate-900"><span>Correction pending since {{ $correction->created_at->format('M j, Y') }}</span><form method="POST" action="{{ route('requests.corrections.cancel', [$recommendation, $correction]) }}">@csrf<button class="font-bold text-red-600">Cancel</button></form></div>
                @endforeach
            </section>
        </div>
    </main>
</x-app-layout>
