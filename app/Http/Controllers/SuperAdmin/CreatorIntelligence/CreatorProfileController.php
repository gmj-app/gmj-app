<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorIntelligence\CreatorProfileRequest;
use App\Models\CreatorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CreatorProfileController extends Controller
{
    public function index(): View
    {
        return view('super-admin.creator-intelligence.profiles.index', ['profiles' => CreatorProfile::withCount('channels')->orderBy('display_name')->paginate(25)]);
    }

    public function create(): View
    {
        return view('super-admin.creator-intelligence.profiles.create');
    }

    public function store(CreatorProfileRequest $request): RedirectResponse
    {
        CreatorProfile::create($request->validated());

        return redirect()->route('superadmin.creator-intelligence.profiles.index')->with('success', 'Creator profile created.');
    }

    public function edit(CreatorProfile $profile): View
    {
        return view('super-admin.creator-intelligence.profiles.edit', compact('profile'));
    }

    public function update(CreatorProfileRequest $request, CreatorProfile $profile): RedirectResponse
    {
        $profile->update($request->validated());

        return redirect()->route('superadmin.creator-intelligence.profiles.index')->with('success', 'Creator profile updated.');
    }
}
