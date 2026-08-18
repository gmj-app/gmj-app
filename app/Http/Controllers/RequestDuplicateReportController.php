<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Services\RequestDuplicateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RequestDuplicateReportController extends Controller
{
    public function store(Request $request, Creator $creator, Recommendation $recommendation, RequestDuplicateService $duplicates): RedirectResponse
    {
        abort_unless((int) $recommendation->creator_id === (int) $creator->id, 404);
        $validated = $request->validate(['duplicate_request_id' => ['required', 'integer', 'exists:recommendations,id']]);
        $other = Recommendation::query()->findOrFail($validated['duplicate_request_id']);
        $duplicates->report($request->user(), $recommendation, $other);

        return redirect()->route('creator.queue', $creator)->with('success', 'Possible duplicate reported. The Creator will review these Requests.');
    }
}
