<x-app-layout>
    <x-slot name="header">
        @include('creators.partials.header', ['section' => 'Audience'])
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @include('creators.partials.navigation')

            <div class="mt-6 rounded-lg bg-white shadow-sm ring-1 ring-gray-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="border-b border-gray-100 p-6 dark:border-slate-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-50">{{ $followers->total() }} {{ Str::plural('follower', $followers->total()) }}</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-slate-300">Registered users who have added this creator to their favorites.</p>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-slate-800">
                    @forelse ($followers as $follower)
                        <div class="flex items-center justify-between gap-4 p-6">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-slate-50">{{ $follower->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-slate-400">{{ $follower->email }}</p>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-slate-400">Followed {{ $follower->pivot->created_at->format('M j, Y') }}</p>
                        </div>
                    @empty
                        <p class="p-10 text-center text-sm text-gray-600 dark:text-slate-300">No followers yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="mt-6">{{ $followers->links() }}</div>
        </div>
    </div>
</x-app-layout>
