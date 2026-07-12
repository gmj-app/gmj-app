<x-public-layout :title="'Plan Testing | '.config('app.name', 'Guide My Journey')">
    <section class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Internal</p>
                        <h1 class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Plan Testing</h1>
                        <p class="mt-2 text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $user->name }} · {{ $user->email }}</p>
                    </div>

                    <span class="inline-flex rounded-full bg-indigo-100 px-3 py-1.5 text-sm font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                        {{ $currentPlanName }}
                    </span>
                </div>

                @if (session('success'))
                    <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                        {{ session('success') }}
                    </div>
                @endif

                <dl class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Creator favorites</dt>
                        <dd class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">{{ $limits['creator_favorites_limit'] }}</dd>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Requests per creator</dt>
                        <dd class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">{{ $limits['suggestions_per_creator_limit'] }}</dd>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Upvotes per creator</dt>
                        <dd class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">{{ $limits['upvotes_per_creator_limit'] }}</dd>
                    </div>
                </dl>

                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    @foreach ($plans as $slug => $plan)
                        <form method="POST" action="{{ route('internal.plan-testing') }}">
                            @csrf
                            <input type="hidden" name="plan_slug" value="{{ $slug }}">
                            <button
                                type="submit"
                                class="inline-flex min-h-11 w-full items-center justify-center rounded-full px-4 py-2.5 text-sm font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900 {{ $currentPlan === $slug ? 'bg-indigo-600 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-700 hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-indigo-500/60 dark:hover:text-indigo-300' }}"
                            >
                                Switch to {{ $plan['name'] }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
