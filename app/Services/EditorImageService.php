<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Image;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EditorImageService
{
    private const array ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const int MAX_SIZE_BYTES = 5 * 1024 * 1024;

    public function store(User $user, UploadedFile $file): Image
    {
        \Illuminate\Support\Facades\Log::info('[EditorImageService] store called', [
            'userId' => $user->id,
            'originalName' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'realPath' => $file->getRealPath(),
            'isValid' => $file->isValid(),
        ]);

        $mime = $file->getMimeType();

        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            \Illuminate\Support\Facades\Log::warning('[EditorImageService] Invalid MIME type', ['mime' => $mime]);

            throw ValidationException::withMessages([
                'editorImage' => 'Invalid image type. Allowed: jpeg, png, gif, webp.',
            ]);
        }

        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            \Illuminate\Support\Facades\Log::warning('[EditorImageService] File too large', ['size' => $file->getSize()]);

            throw ValidationException::withMessages([
                'editorImage' => 'Image must be under 5MB.',
            ]);
        }

        $extension = $file->guessExtension() ?? 'jpg';
        $filename = Str::uuid()->toString() . '.' . $extension;
        $directory = 'editor-images/' . $user->id;

        \Illuminate\Support\Facades\Log::info('[EditorImageService] Storing file', [
            'directory' => $directory,
            'filename' => $filename,
            'disk' => 'private',
        ]);

        $path = $file->storeAs($directory, $filename, 'private');

        if (! $path) {
            \Illuminate\Support\Facades\Log::error('[EditorImageService] storeAs returned false/null', [
                'directory' => $directory,
                'filename' => $filename,
            ]);
        }

        \Illuminate\Support\Facades\Log::info('[EditorImageService] File stored', ['path' => $path]);

        return Image::create([
            'user_id' => $user->id,
            'path' => $path,
            'disk' => 'private',
            'alt' => $file->getClientOriginalName(),
        ]);
    }
}
