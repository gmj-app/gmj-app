<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ToolsAdminController extends Controller
{
    public function __invoke(): View
    {
        return view('tools.admin');
    }
}
