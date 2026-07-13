<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCreatorProfileRequest;
use App\Models\Creator;
use App\Services\Accolades\AccoladeShowcaseService;
use App\Services\CreatorProfileUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CreatorSettingsController extends Controller
{
    public function __construct(private readonly CreatorProfileUpdateService $profiles) {}

    public function edit(Request $request, Creator $creator, AccoladeShowcaseService $showcase): View
    {
        Gate::authorize('manage', $creator);

        $creatorAccolades = $showcase->forSubject('creator', $creator->id);

        return view('creators.settings', compact('creator', 'creatorAccolades'));
    }

    public function update(UpdateCreatorProfileRequest $request, Creator $creator): RedirectResponse
    {
        Gate::authorize('manage', $creator);
        $this->profiles->update($creator, $request->validated(), $request->allFiles());

        return redirect()
            ->route('creators.settings.edit', $creator)
            ->with('success', 'Creator settings updated.');
    }

    public function deactivate(Request $request, Creator $creator): RedirectResponse
    {
        Gate::authorize('manage', $creator);

        $creator->update([
            'status' => 'inactive',
            'deactivated_at' => now(),
            'submissions_open' => false,
        ]);

        return redirect()
            ->route('creators.dashboard', $creator)
            ->with('success', 'Creator page deactivated.');
    }
}
