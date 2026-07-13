<?php

namespace App\Http\Controllers;

use App\Services\Accolades\GuideAccoladeSummaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideAccoladeIndexController extends Controller
{
    public function __invoke(Request $request, GuideAccoladeSummaryService $summaries): View
    {
        return view('accolades.index', [
            'accoladeSummary' => $summaries->forPrivatePage($request->user()),
        ]);
    }
}
