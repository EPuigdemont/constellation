<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageServeController extends Controller
{
    public function __invoke(Request $request, Image $image): StreamedResponse
    {
        Gate::authorize('view', $image);

        $disk = Storage::disk($image->disk);

        abort_unless($disk->exists($image->path), 404);

        $mime = $disk->mimeType($image->path);

        return $disk->download($image->path, null, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
