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
        $mime = $file->getMimeType();

        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'editorImage' => 'Invalid image type. Allowed: jpeg, png, gif, webp.',
            ]);
        }

        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            throw ValidationException::withMessages([
                'editorImage' => 'Image must be under 5MB.',
            ]);
        }

        $extension = $file->guessExtension() ?? 'jpg';
        $filename = Str::uuid()->toString() . '.' . $extension;
        $directory = 'editor-images/' . $user->id;

        $path = $file->storeAs($directory, $filename, 'private');

        return Image::create([
            'user_id' => $user->id,
            'path' => $path,
            'disk' => 'private',
            'alt' => $file->getClientOriginalName(),
            'is_public' => false,
        ]);
    }
}
