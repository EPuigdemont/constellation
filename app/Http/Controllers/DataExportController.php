<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DataExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    public function __invoke(Request $request, DataExportService $service): StreamedResponse
    {
        $user = $request->user();
        $zipPath = $service->export($user);
        $filename = 'constellation_export_' . now()->format('Y-m-d_His') . '.zip';

        return response()->streamDownload(function () use ($zipPath) {
            readfile($zipPath);
            @unlink($zipPath);
        }, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }
}
