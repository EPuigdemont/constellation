<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Image;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EditorImageService
{
    private const array ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const int MAX_SIZE_BYTES = 5 * 1024 * 1024;

    public function store(User $user, UploadedFile $file): Image
    {
        Log::info('[EditorImageService] store called', [
            'userId' => $user->id,
            'originalName' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'realPath' => $file->getRealPath(),
            'isValid' => $file->isValid(),
        ]);

        $mime = $file->getMimeType();

        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            Log::warning('[EditorImageService] Invalid MIME type', ['mime' => $mime]);

            throw ValidationException::withMessages([
                'editorImage' => 'Invalid image type. Allowed: jpeg, png, gif, webp.',
            ]);
        }

        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            Log::warning('[EditorImageService] File too large', ['size' => $file->getSize()]);

            throw ValidationException::withMessages([
                'editorImage' => 'Image must be under 5MB.',
            ]);
        }

        $extension = $file->guessExtension() ?? 'jpg';
        $filename = Str::uuid()->toString().'.'.$extension;
        $directory = 'editor-images/'.$user->id;

        Log::info('[EditorImageService] Storing file', [
            'directory' => $directory,
            'filename' => $filename,
            'disk' => 'private',
        ]);

        $path = $file->storeAs($directory, $filename, 'private');

        if ($path === false) {
            Log::error('[EditorImageService] storeAs returned false', [
                'directory' => $directory,
                'filename' => $filename,
            ]);

            throw new \RuntimeException('Failed to store editor image file.');
        }

        Log::info('[EditorImageService] File stored', ['path' => $path]);

        $dimensions = $this->extractDimensions($file);

        return Image::create([
            'user_id' => $user->id,
            'path' => $path,
            'disk' => 'private',
            'alt' => $file->getClientOriginalName(),
            'image_width' => $dimensions['width'],
            'image_height' => $dimensions['height'],
        ]);
    }

    /** @return array{width: int|null, height: int|null} */
    private function extractDimensions(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();

        if (! is_string($realPath) || $realPath === '') {
            return ['width' => null, 'height' => null];
        }

        $size = @getimagesize($realPath);

        if ($size === false) {
            return ['width' => null, 'height' => null];
        }

        return [
            'width' => $size[0],
            'height' => $size[1],
        ];
    }
}
