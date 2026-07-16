<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\GameDay;
use App\Models\GameRun;
use App\Services\DailyJourney\RunService;
use App\Services\SuperAdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(Request $request): View
    {
        $runs = GameRun::query()->with(['user', 'day'])->when($request->status, fn ($q, $v) => $q->where('validation_status', $v))->when($request->q, fn ($q, $v) => $q->whereHas('user', fn ($u) => $u->where('public_display_name', 'like', '%'.$v.'%')->orWhere('public_handle', 'like', '%'.$v.'%')))->latest()->paginate(30)->withQueryString();

        return view('super-admin.game.index', ['runs' => $runs, 'days' => GameDay::query()->latest('local_date')->limit(30)->get()]);
    }

    public function show(GameRun $run): View
    {
        return view('super-admin.game.show', ['run' => $run->load(['user', 'day', 'session'])]);
    }

    public function invalidate(Request $request, GameRun $run, RunService $runs, SuperAdminAuditService $audit): RedirectResponse
    {
        $data = $request->validate(['reason' => 'required|string|min:10|max:1000']);
        $before = $run->only(['validation_status', 'invalidated_at']);
        $run->update(['validation_status' => 'invalidated', 'invalidated_at' => now(), 'invalidated_by' => $request->user()->id, 'invalidation_reason' => $data['reason']]);
        $runs->recalculateBest($run->game_day_id, $run->user_id);
        $audit->record($request->user(), $run, 'game.run.invalidated', 'Invalidated Daily Journey run', $before, $run->only(['validation_status', 'invalidated_at']), ['reason' => $data['reason']], $request);

        return back()->with('success', $run->day->status === 'finalized' ? 'Run invalidated. This finalized day requires deliberate repair.' : 'Run invalidated and leaderboard recalculated.');
    }
}
