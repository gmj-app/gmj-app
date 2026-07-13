<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Creator;
use App\Models\User;
use App\Models\UserAccolade;
use App\Services\Accolades\AccoladeDefinitionRepository;
use App\Services\Accolades\AccoladeEvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccoladeController extends Controller
{
    public function index(Request $request, AccoladeDefinitionRepository $definitions): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'track' => ['nullable', 'string'], 'accolade_key' => ['nullable', 'string']]);
        $awards = UserAccolade::query()->with('user')
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->whereHas('user', fn ($users) => $users->where('email', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")))
            ->when($filters['track'] ?? null, fn ($query, $track) => $query->where('track', $track))
            ->when($filters['accolade_key'] ?? null, fn ($query, $key) => $query->where('accolade_key', $key))
            ->latest('awarded_at')->paginate(50)->withQueryString();

        return view('super-admin.accolades.index', ['awards' => $awards, 'definitions' => $definitions->all(), 'filters' => $filters]);
    }

    public function evaluateGuide(Request $request, User $user, AccoladeEvaluationService $evaluation): RedirectResponse
    {
        $result = $evaluation->evaluateGuide($user, source: ['source' => 'super_admin_evaluation', 'actor_user_id' => $request->user()->id]);

        return back()->with('success', "Guide evaluated; {$result->newAwards->count()} new accolades earned.");
    }

    public function evaluateCreator(Request $request, Creator $creator, AccoladeEvaluationService $evaluation): RedirectResponse
    {
        $result = $evaluation->evaluateCreator($creator, source: ['source' => 'super_admin_evaluation', 'actor_user_id' => $request->user()->id]);

        return back()->with('success', "Creator evaluated; {$result->newAwards->count()} new accolades earned.");
    }

    public function rebuild(Request $request, AccoladeEvaluationService $evaluation): RedirectResponse
    {
        $validated = $request->validate(['subject_type' => ['required', 'in:guide,creator'], 'subject_id' => ['required', 'integer']]);
        $evaluation->evaluateSubject($validated['subject_type'], $validated['subject_id'], source: ['source' => 'super_admin_progress_rebuild'], award: false);

        return back()->with('success', 'Accolade progress rebuilt without changing earned history.');
    }
}
