<x-super-admin-layout title="Advertisements">
    <div class="mb-6 flex items-center justify-between"><div><h2 class="text-2xl font-extrabold">Advertisements</h2><p class="text-sm text-slate-500">Managed tiles in the Popular Creators grid.</p></div><a href="{{ route('super-admin.ads.create') }}" class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white">Create advertisement</a></div>
    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-950"><tr><th class="p-4">Ad</th><th class="p-4">Placement</th><th class="p-4">Status / schedule</th><th class="p-4">Destination</th><th class="p-4">Performance</th><th class="p-4">Actions</th></tr></thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
        @forelse ($advertisements as $advertisement)
            <tr><td class="p-4"><div class="flex items-center gap-3"><img src="{{ $advertisement->imageUrl() }}" alt="" class="h-16 w-12 rounded-lg object-cover" onerror="this.classList.add('invisible')"><div><p class="font-bold">{{ $advertisement->internal_name }}</p><p class="text-slate-500">{{ $advertisement->advertiser_name ?: 'No advertiser name' }}</p></div></div></td>
            <td class="p-4"><span class="font-bold">{{ $advertisement->placement }}</span>@if ($conflicts->contains($advertisement->placement))<p class="text-xs font-bold text-amber-600">Placement conflict</p>@endif</td>
            <td class="p-4"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold dark:bg-slate-800">{{ $advertisement->statusLabel() }}</span><p class="mt-2 text-xs text-slate-500">{{ $advertisement->starts_at?->format('M j, Y g:i A') ?: 'Immediately' }} – {{ $advertisement->ends_at?->format('M j, Y g:i A') ?: 'No end' }}</p></td>
            <td class="p-4">{{ parse_url($advertisement->destination_url, PHP_URL_HOST) }}</td><td class="p-4">{{ number_format($advertisement->click_count) }} clicks<br><span class="text-xs text-slate-500">Impressions not tracked yet</span></td>
            <td class="p-4"><div class="flex flex-wrap gap-2"><a href="{{ route('super-admin.ads.edit', $advertisement) }}" class="font-bold text-indigo-600">Edit</a><form method="POST" action="{{ route('super-admin.ads.toggle', $advertisement) }}">@csrf @method('PATCH')<button class="font-bold text-amber-600">{{ $advertisement->is_active ? 'Disable' : 'Enable' }}</button></form><form method="POST" action="{{ route('super-admin.ads.destroy', $advertisement) }}" onsubmit="return confirm('Delete this advertisement?')">@csrf @method('DELETE')<button class="font-bold text-red-600">Delete</button></form></div></td></tr>
        @empty <tr><td colspan="6" class="p-10 text-center text-slate-500">No advertisements yet.</td></tr> @endforelse
        </tbody></table>
    </div>
</x-super-admin-layout>
