<?php

namespace App\Http\Controllers;

use App\Models\HomepageAdvertisement;
use Illuminate\Http\RedirectResponse;

class AdvertisementClickController extends Controller
{
    public function __invoke(HomepageAdvertisement $advertisement): RedirectResponse
    {
        abort_unless($advertisement->isCurrentlyActive(), 404);
        $advertisement->increment('click_count');

        return redirect()->away($advertisement->safeDestinationUrl());
    }
}
