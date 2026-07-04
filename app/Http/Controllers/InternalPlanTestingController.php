<?php

namespace App\Http\Controllers;

use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InternalPlanTestingController extends Controller
{
    public function __construct(
        private readonly PlanEntitlementService $plans,
    ) {}

    public function edit(Request $request): View
    {
        abort_unless($request->user()?->canTestPaidPlans(), 404);

        return view('internal.plan-testing', [
            'plans' => $this->plans->plans(),
            'currentPlan' => $this->plans->getUserPlan($request->user()),
            'currentPlanName' => $this->plans->getUserPlanName($request->user()),
            'limits' => $this->plans->getLimitsForUser($request->user()),
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canTestPaidPlans(), 404);

        $validated = $request->validate([
            'plan_slug' => ['required', Rule::in($this->plans->validPlanSlugs())],
        ]);

        $request->user()->forceFill([
            'plan_slug' => $validated['plan_slug'],
        ])->save();

        return redirect()
            ->route('internal.plan-testing')
            ->with('success', 'Plan updated for testing.');
    }
}
