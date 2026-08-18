<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\RequestDuplicateCase;
use App\Services\RequestDuplicateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CreatorDuplicateCaseController extends Controller
{
    public function index(Creator $creator): View
    {
        Gate::authorize('manage', $creator);
        $cases = RequestDuplicateCase::query()->where('creator_id', $creator->id)->where('status', 'pending')
            ->with(['requestLow.submittedBy:id,name,public_display_name,public_handle', 'requestHigh.submittedBy:id,name,public_display_name,public_handle'])
            ->withCount('reports')->withMin('reports', 'created_at')->withMax('reports', 'created_at')->latest()->paginate(25);

        return view('creators.duplicates.index', compact('creator', 'cases'));
    }

    public function resolve(Request $request, Creator $creator, RequestDuplicateCase $duplicateCase, RequestDuplicateService $duplicates): RedirectResponse
    {
        Gate::authorize('manage', $creator);
        abort_unless((int) $duplicateCase->creator_id === (int) $creator->id, 404);
        $validated = $request->validate(['resolution' => ['required', Rule::in(['not_duplicate', 'keep_a', 'keep_b'])], 'confirm' => [Rule::requiredIf($request->input('resolution') !== 'not_duplicate'), 'nullable', 'accepted']]);
        $duplicates->resolve($duplicateCase, $request->user(), $validated['resolution'], $request);

        return back()->with('success', $validated['resolution'] === 'not_duplicate' ? 'Both Requests were kept.' : 'Duplicate Requests merged successfully.');
    }
}
