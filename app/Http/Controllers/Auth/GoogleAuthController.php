<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GuideAccoladeService;
use App\Services\GuideNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $this->configureGoogleService();

        $missingGoogleCredentials = $this->missingGoogleCredentials();

        if ($missingGoogleCredentials !== []) {
            return redirect()->route('login')
                ->with(
                    'status',
                    'Google sign-in is not configured yet. Missing '.implode(' and ', $missingGoogleCredentials).'.',
                );
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')
                ->with('status', 'Google sign-in could not be completed. Please try again.');
        }

        $googleId = (string) $googleUser->getId();
        $email = $googleUser->getEmail();

        if (blank($email)) {
            return redirect()->route('login')
                ->with('status', 'Google did not return an email address. Please try again or use another Google account.');
        }

        $user = User::query()
            ->where('google_id', $googleId)
            ->first();

        if (! $user) {
            $user = User::query()
                ->where('email', $email)
                ->first();
        }

        if ($user) {
            $user->forceFill([
                'google_id' => $user->google_id ?: $googleId,
                'avatar_url' => $user->avatar_url ?: $googleUser->getAvatar(),
                'auth_provider' => $user->auth_provider ?: 'google',
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();
        } else {
            $user = User::query()->create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleId,
                'avatar_url' => $googleUser->getAvatar(),
                'auth_provider' => 'google',
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(64)),
            ]);
        }

        app(GuideNumberService::class)->assignIfMissing($user);
        app(GuideAccoladeService::class)->awardEarlyGuideAccolades($user);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return $this->redirectToSafeIntended($request);
    }

    private function redirectToSafeIntended(Request $request): RedirectResponse
    {
        $intended = (string) $request->session()->pull('url.intended', '');

        if (! $request->user()?->hasCompletedPublicProfile()) {
            if ($this->isSafeRedirectUrl($request, $intended)) {
                $request->session()->put('public_profile.intended', $this->relativeRedirectUrl($request, $intended));
            }

            return redirect()->route('profile.setup');
        }

        if ($this->isSafeRedirectUrl($request, $intended)) {
            return redirect()->to($intended);
        }

        return redirect()->route('dashboard');
    }

    private function configureGoogleService(): void
    {
        config([
            'services.google.redirect' => $this->normalizeGoogleRedirectUri(),
        ]);
    }

    private function normalizeGoogleRedirectUri(): string
    {
        $redirect = trim((string) config('services.google.redirect'));

        $callbackPath = '/auth/google/callback';

        if (! $redirect) {
            return route('auth.google.callback');
        }

        $path = parse_url($redirect, PHP_URL_PATH);

        if ($path === null || $path === '' || $path === '/') {
            return rtrim($redirect, '/').$callbackPath;
        }

        return $redirect;
    }

    /**
     * @return array<int, string>
     */
    private function missingGoogleCredentials(): array
    {
        return collect([
            'GOOGLE_CLIENT_ID' => config('services.google.client_id'),
            'GOOGLE_CLIENT_SECRET' => config('services.google.client_secret'),
        ])
            ->filter(fn ($value) => blank($value))
            ->keys()
            ->all();
    }

    private function isSafeRedirectUrl(Request $request, string $url): bool
    {
        if ($url === '' || str_contains($url, '\\') || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return false;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return parse_url($url) !== false;
        }

        $parts = parse_url($url);

        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $allowedHost = parse_url(config('app.url'), PHP_URL_HOST) ?: $request->getHost();

        return in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            && ($parts['host'] ?? null) === $allowedHost;
    }

    private function relativeRedirectUrl(Request $request, string $url): string
    {
        if (str_starts_with($url, '/')) {
            return $url;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return route('dashboard', absolute: false);
        }

        $path = $parts['path'] ?? route('dashboard', absolute: false);
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $path.$query.$fragment;
    }
}
