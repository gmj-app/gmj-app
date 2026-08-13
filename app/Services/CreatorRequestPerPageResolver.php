<?php

namespace App\Services;

use Illuminate\Http\Request;

class CreatorRequestPerPageResolver
{
    public const DEFAULT = 50;

    public const ALLOWED = [10, 25, 50, 100];

    public function resolve(Request $request): int
    {
        if ($request->query->has('per_page')) {
            $perPage = $this->validated($request->query('per_page'));

            if ($perPage === null) {
                return self::DEFAULT;
            }

            $request->session()->put($this->sessionKey($request), $perPage);

            return $perPage;
        }

        return $this->validated($request->session()->get($this->sessionKey($request)))
            ?? self::DEFAULT;
    }

    /** @return list<int> */
    public function options(): array
    {
        return self::ALLOWED;
    }

    private function validated(mixed $value): ?int
    {
        if (! is_int($value) && ! is_string($value)) {
            return null;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_INT);

        return in_array($normalized, self::ALLOWED, true) ? $normalized : null;
    }

    private function sessionKey(Request $request): string
    {
        return $request->user()
            ? "creator_requests_per_page.users.{$request->user()->getAuthIdentifier()}"
            : 'creator_requests_per_page.guest';
    }
}
