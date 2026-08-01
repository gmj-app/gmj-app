<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\User;
use App\Services\PublicGuideMetricsService;
use App\ViewModels\PublicGuideAccoladeViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicProfileController extends Controller
{
    public function __invoke(
        Request $request,
        RecommendationController $creators,
        PublicGuideProfileController $guides,
        PublicGuideAccoladeViewModel $accolades,
        PublicGuideMetricsService $metrics,
    ): View|RedirectResponse {
        $requestedHandle = (string) collect($request->route()->parameters())->first();
        $handle = strtolower($requestedHandle);

        $creator = Creator::query()->where('slug', $handle)->first();

        if ($creator) {
            if ($requestedHandle !== $creator->slug) {
                return $this->canonicalRedirect($request, $creator->slug);
            }

            return $creators->showCreatorQueue($request, $creator);
        }

        $guide = User::query()
            ->where('public_handle', $handle)
            ->where('public_profile_enabled', true)
            ->whereNotNull('public_display_name')
            ->with('guideAccolades')
            ->firstOrFail();

        if ($requestedHandle !== $guide->public_handle) {
            return $this->canonicalRedirect($request, (string) $guide->public_handle);
        }

        return $guides->show($request, $guide, $accolades, $metrics);
    }

    private function canonicalRedirect(Request $request, string $handle): RedirectResponse
    {
        $url = route('creator.queue', ['creator' => $handle]);

        if ($request->getQueryString()) {
            $url .= '?'.$request->getQueryString();
        }

        return redirect()->to($url, 301);
    }
}
