<?php

namespace App\Http\Controllers;

use App\Services\GuideActivityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyActivityController extends Controller
{
    public function index(Request $request, GuideActivityService $activity): View
    {
        $type = in_array($request->query('type'), ['votes', 'suggestions', 'published'], true)
            ? $request->query('type')
            : 'all';

        return view('activity.index', [
            ...$activity->forUser($request->user(), $type),
            'type' => $type,
        ]);
    }
}
