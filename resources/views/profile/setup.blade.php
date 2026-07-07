<x-app-layout>
    <x-slot name="title">Choose your public guide name</x-slot>

    <div class="py-10 sm:py-12">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                <div class="mb-6">
                    <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Guide setup</p>
                    <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">Choose your public guide name</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        This is the name other people will see when you suggest, vote, or support recommendations. You can use a nickname, channel name, or community name.
                    </p>
                </div>

                @include('profile.partials.public-identity-form', [
                    'user' => $user,
                    'action' => route('profile.setup.store'),
                    'method' => 'post',
                    'submitLabel' => 'Save and continue',
                    'showAccountContext' => true,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
