<?php

namespace App\Http\Controllers;

use App\Models\UserAccolade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GuideAccoladeController extends Controller
{
    public function feature(Request $request): RedirectResponse
    {
        $validated = $request->validate(['accolade_id' => ['required', 'integer']]);
        $award = UserAccolade::query()->whereKey($validated['accolade_id'])
            ->where('user_id', $request->user()->id)->where('subject_type', 'guide')
            ->where('subject_id', $request->user()->id)->where('is_public', true)->firstOrFail();

        DB::transaction(function () use ($award, $request): void {
            UserAccolade::query()->where('subject_type', 'guide')->where('subject_id', $request->user()->id)
                ->update(['is_featured' => false, 'featured_order' => null]);
            $metadata = $award->metadata ?? [];
            $award->update(['is_featured' => true, 'featured_order' => 1, 'metadata' => [...$metadata, 'manual_featured' => true]]);
        });
        Cache::forget("accolades:guide:{$request->user()->id}");

        return back()->with('success', 'Featured accolade updated.');
    }
}
