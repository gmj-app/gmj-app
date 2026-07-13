<?php

namespace App\Http\Controllers;

use App\ViewModels\GuideAccoladePageViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideAccoladeIndexController extends Controller
{
    public function __invoke(Request $request, GuideAccoladePageViewModel $page): View
    {
        return view('accolades.index', [
            'accoladeSummary' => $page->forUser($request->user()),
        ]);
    }
}
