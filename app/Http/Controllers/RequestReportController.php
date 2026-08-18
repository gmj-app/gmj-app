<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\RequestReport;
use App\Services\RequestReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RequestReportController extends Controller
{
    public function store(Request $request, Creator $creator, Recommendation $recommendation, RequestReportService $reports): RedirectResponse
    {
        abort_unless((int) $recommendation->creator_id === (int) $creator->id, 404);
        $data = $request->validate(['reason' => ['required', Rule::in(array_keys(RequestReport::REASONS))], 'details' => ['nullable', 'string', 'max:500']]);
        $reports->report($request->user(), $recommendation, $data['reason'], $data['details'] ?? null);

        return redirect()->route('creator.queue', $creator)->with('success', 'Report submitted. The Creator will review this Request.');
    }
}
