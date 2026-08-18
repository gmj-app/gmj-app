<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Services\RequestReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CreatorRequestReportController extends Controller
{
    public function index(Creator $creator): View
    {
        Gate::authorize('manage', $creator);
        $items = $creator->recommendations()->whereHas('reports', fn ($q) => $q->where('status', 'pending'))->with(['submittedBy:id,name,public_display_name', 'reports' => fn ($q) => $q->where('status', 'pending')->with('reporter:id,name,email')->oldest()])->withEffectiveVoteTotal()->paginate(25);

        return view('creators.reports.index', compact('creator', 'items'));
    }

    public function resolve(Request $request, Creator $creator, Recommendation $recommendation, RequestReportService $reports): RedirectResponse
    {
        Gate::authorize('manage', $creator);
        abort_unless((int) $recommendation->creator_id === (int) $creator->id, 404);
        $data = $request->validate(['resolution' => ['required', Rule::in(['kept', 'hidden'])]]);
        $reports->resolve($recommendation, $request->user(), $data['resolution'], $request);

        return back()->with('success', $data['resolution'] === 'hidden' ? 'Request hidden as inappropriate.' : 'Request kept active.');
    }
}
