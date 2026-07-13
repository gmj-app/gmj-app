<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOwnRequestPresentationRequest;
use App\Models\Recommendation;
use App\Models\RequestIdentityCorrection;
use App\Services\RequestPresentationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GuideRequestPresentationController extends Controller
{
    public function edit(Request $request, Recommendation $recommendation): View
    {
        Gate::authorize('updateOwnPresentation', $recommendation);
        $recommendation->load(['creator', 'identityCorrections' => fn ($query) => $query->where('requested_by', $request->user()->id)->latest()]);

        return view('recommendations.edit-presentation', compact('recommendation'));
    }

    public function update(UpdateOwnRequestPresentationRequest $request, Recommendation $recommendation, RequestPresentationService $service): RedirectResponse
    {
        $revision = $service->update($recommendation, $request->user(), $request->validated());

        return back()->with('success', $revision ? 'Your request presentation was updated.' : 'No presentation changes were made.');
    }

    public function correction(Request $request, Recommendation $recommendation): RedirectResponse
    {
        Gate::authorize('updateOwnPresentation', $recommendation);
        $validated = $request->validate([
            'proposed_url' => ['nullable', 'url', 'max:2048', 'required_without:proposed_topic'],
            'proposed_topic' => ['nullable', 'string', 'max:255', 'required_without:proposed_url'],
            'explanation' => ['required', 'string', 'max:2000'],
        ]);
        $recommendation->identityCorrections()->create([...$validated, 'requested_by' => $request->user()->id]);

        return back()->with('success', 'Correction submitted for creator review. The live request was not changed.');
    }

    public function cancel(Request $request, Recommendation $recommendation, RequestIdentityCorrection $correction): RedirectResponse
    {
        abort_unless((int) $correction->recommendation_id === (int) $recommendation->id && (int) $correction->requested_by === (int) $request->user()->id && $correction->status === 'pending', 404);
        $correction->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return back()->with('success', 'Correction request cancelled.');
    }
}
