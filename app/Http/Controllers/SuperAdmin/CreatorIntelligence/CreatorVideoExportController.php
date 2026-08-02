<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Services\CreatorIntelligence\Videos\CreatorVideoExportService;
use App\Services\CreatorIntelligence\Videos\CreatorVideoQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CreatorVideoExportController extends Controller
{
    public function __invoke(Request $request, CreatorVideoQuery $query, CreatorVideoExportService $export): StreamedResponse
    {
        return $export->download($query->build($request));
    }
}
