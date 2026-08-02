<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorIntelligence\CreatorChannelRequest;
use App\Models\CreatorChannel;
use App\Models\CreatorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CreatorChannelController extends Controller
{
    public function index(): View
    {
        return view('super-admin.creator-intelligence.channels.index', ['channels' => CreatorChannel::with('profile')->orderBy('channel_name')->paginate(25)]);
    }

    public function create(): View
    {
        return view('super-admin.creator-intelligence.channels.create', ['profiles' => CreatorProfile::orderBy('display_name')->get()]);
    }

    public function store(CreatorChannelRequest $request): RedirectResponse
    {
        CreatorChannel::create($request->validated());

        return redirect()->route('superadmin.creator-intelligence.channels.index')->with('success', 'Creator channel created.');
    }

    public function edit(CreatorChannel $channel): View
    {
        return view('super-admin.creator-intelligence.channels.edit', ['channel' => $channel, 'profiles' => CreatorProfile::orderBy('display_name')->get()]);
    }

    public function update(CreatorChannelRequest $request, CreatorChannel $channel): RedirectResponse
    {
        $channel->update($request->validated());

        return redirect()->route('superadmin.creator-intelligence.channels.index')->with('success', 'Creator channel updated.');
    }
}
