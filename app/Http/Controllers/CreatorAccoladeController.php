<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\UserAccolade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreatorAccoladeController extends Controller
{
    public function update(Request $request, Creator $creator): RedirectResponse
    {
        Gate::authorize('manage', $creator);
        $validated = $request->validate([
            'accolade_ids' => ['nullable', 'array', 'max:3'],
            'accolade_ids.*' => ['integer', 'distinct'],
        ]);
        $ids = collect($validated['accolade_ids'] ?? [])->map(fn ($id) => (int) $id)->values();
        $valid = UserAccolade::query()->whereIn('id', $ids)->where('subject_type', 'creator')
            ->where('subject_id', $creator->id)->where('is_public', true)->pluck('id');
        abort_unless($valid->count() === $ids->count(), 422);

        DB::transaction(function () use ($creator, $ids): void {
            UserAccolade::query()->where('subject_type', 'creator')->where('subject_id', $creator->id)
                ->update(['is_featured' => false, 'featured_order' => null]);
            foreach ($ids as $index => $id) {
                UserAccolade::query()->whereKey($id)->update(['is_featured' => true, 'featured_order' => $index + 1]);
            }
        });
        Cache::forget("accolades:creator:{$creator->id}");

        return back()->with('success', 'Featured creator accolades updated.');
    }
}
