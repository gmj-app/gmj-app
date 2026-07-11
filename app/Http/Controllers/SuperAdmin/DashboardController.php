<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\HomepageAdvertisement;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('super-admin.dashboard', ['advertisementCount' => HomepageAdvertisement::count()]);
    }
}
