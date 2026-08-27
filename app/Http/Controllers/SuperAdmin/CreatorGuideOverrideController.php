<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Creator;
use App\Models\CreatorGuideOverride;
use App\Models\User;
use App\Services\SuperAdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CreatorGuideOverrideController extends Controller
{
    public function __construct(private readonly SuperAdminAuditService $audit) {}

    public function store(Request $request, Creator $creator): RedirectResponse
    {
        $data = $this->validateOverride($request, true);
        $email = mb_strtolower(trim($data['guide_email']));
        $guide = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $guide) {
            throw ValidationException::withMessages(['guide_email' => 'No Guide account was found with that email.']);
        }
        if ($creator->guideRequestOverrides()->where('user_id', $guide->id)->exists()) {
            throw ValidationException::withMessages(['guide_email' => 'This Guide already has an override. Edit the current limit below.']);
        }

        $override = $creator->guideRequestOverrides()->create(['user_id' => $guide->id, 'request_limit' => $data['request_limit'], 'created_by_user_id' => $request->user()->id, 'updated_by_user_id' => $request->user()->id]);
        $this->audit->record($request->user(), $creator, 'creator.super_guide.added', 'Super Guide request-limit override added.', [], ['request_limit' => $override->request_limit], ['guide_user_id' => $guide->id], $request);

        return back()->with('success', 'Super Guide added.');
    }

    public function update(Request $request, Creator $creator, CreatorGuideOverride $override): RedirectResponse
    {
        $this->ensureCreator($creator, $override);
        $data = $this->validateOverride($request);
        $oldLimit = $override->request_limit;
        $override->update(['request_limit' => $data['request_limit'], 'updated_by_user_id' => $request->user()->id]);
        $this->audit->record($request->user(), $creator, 'creator.super_guide.updated', 'Super Guide request-limit override updated.', ['request_limit' => $oldLimit], ['request_limit' => $override->request_limit], ['guide_user_id' => $override->user_id], $request);

        return back()->with('success', 'Super Guide limit updated.');
    }

    public function destroy(Request $request, Creator $creator, CreatorGuideOverride $override): RedirectResponse
    {
        $this->ensureCreator($creator, $override);
        $oldLimit = $override->request_limit;
        $guideId = $override->user_id;
        $override->delete();
        $this->audit->record($request->user(), $creator, 'creator.super_guide.removed', 'Super Guide request-limit override removed.', ['request_limit' => $oldLimit], ['request_limit' => (int) config('request_limits.default', 3)], ['guide_user_id' => $guideId], $request);

        return back()->with('success', 'Super Guide override removed. Existing Requests were not changed.');
    }

    private function validateOverride(Request $request, bool $includeEmail = false): array
    {
        return $request->validate(['guide_email' => [$includeEmail ? 'required' : 'sometimes', 'string', 'email', 'max:255'], 'request_limit' => ['required', 'integer', 'min:'.config('request_limits.override_min', 4), 'max:'.config('request_limits.override_max', 50)]]);
    }

    private function ensureCreator(Creator $creator, CreatorGuideOverride $override): void
    {
        abort_unless((int) $override->creator_id === (int) $creator->id, 404);
    }
}
