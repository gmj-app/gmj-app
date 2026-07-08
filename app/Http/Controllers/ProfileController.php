<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\PublicIdentityRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function setup(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasCompletedPublicProfile()) {
            return redirect()->route('dashboard');
        }

        return view('profile.setup', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updatePublicIdentity(PublicIdentityRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            ...$request->validated(),
            'public_profile_completed_at' => $request->user()->public_profile_completed_at ?? now(),
        ])->save();

        $intended = (string) $request->session()->pull('public_profile.intended', '');

        if ($intended !== '' && str_starts_with($intended, '/') && ! str_starts_with($intended, '//')) {
            return redirect()->to($intended)->with('status', 'public-identity-updated');
        }

        return Redirect::route('profile.edit')->with('status', 'public-identity-updated');
    }

    public function updateDisplayNamePrompt(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'public_display_name' => [
                'required',
                'string',
                'min:2',
                'max:40',
                'not_regex:/[<>]/',
                'not_regex:/\b(?:https?:\/\/|www\.)/i',
                fn (string $attribute, mixed $value, \Closure $fail) => strcasecmp(trim((string) $value), 'Guide') === 0
                    ? $fail('Please choose a display name other than "Guide".')
                    : null,
            ],
        ], [
            'public_display_name.max' => 'Display name must be 40 characters or fewer.',
            'public_display_name.not_regex' => "Display name can't include links or HTML.",
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'displayNamePrompt')
                ->withInput();
        }

        $validated = $validator->validated();
        $displayName = trim((string) $validated['public_display_name']);
        $displayName = preg_replace('/\s+/', ' ', $displayName) ?: $displayName;

        $request->user()->forceFill([
            'public_display_name' => $displayName,
            'public_profile_completed_at' => $request->user()->public_profile_completed_at ?? now(),
        ])->save();

        return back()->with('status', 'display-name-updated');
    }

    public function dismissDisplayNamePrompt(Request $request): RedirectResponse
    {
        if ($request->boolean('dont_show_again')) {
            $request->user()->forceFill([
                'display_name_prompt_dismissed_at' => now(),
            ])->save();
        }

        return back()->with('status', 'display-name-prompt-dismissed');
    }

    public function completeSetup(PublicIdentityRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            ...$request->validated(),
            'public_profile_completed_at' => now(),
        ])->save();

        $intended = (string) $request->session()->pull('public_profile.intended', '');

        if ($intended !== '' && str_starts_with($intended, '/') && ! str_starts_with($intended, '//')) {
            return redirect()->to($intended)->with('status', 'public-profile-completed');
        }

        return Redirect::route('dashboard')->with('status', 'public-profile-completed');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
