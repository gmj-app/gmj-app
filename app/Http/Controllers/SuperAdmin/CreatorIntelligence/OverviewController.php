<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Models\CreatorChannel;
use App\Models\CreatorProfile;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(): View
    {
        return view('super-admin.creator-intelligence.overview', ['profileCount' => CreatorProfile::count(), 'channelCount' => CreatorChannel::count()]);
    }
}
