<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Export\PostCsvExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostExportController extends Controller
{
    /**
     * Hands the screen's filters to the exporter and streams the result.
     */
    public function export(Request $request, PostCsvExporter $exporter): StreamedResponse
    {
        return $exporter->export(
            ids: array_values(array_map('intval', (array) $request->query('ids', []))),
            search: trim((string) $request->query('search', '')),
            status: (string) $request->query('status', ''),
            template: $request->boolean('template'),
        );
    }
}
