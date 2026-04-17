<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tier;
use App\Models\Image;
use App\Models\User;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EditorImageService
{
    private const array ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

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
        if (! is_string($mime) || ! in_array($mime, self::ALLOWED_MIMES, true)) {
            Log::warning('[EditorImageService] Invalid MIME type', ['mime' => $mime]);

            throw ValidationException::withMessages([
                'editorImage' => 'Invalid image type. Allowed: jpeg, png, gif, webp.',
            ]);
        }

        $maxSizeBytes = $this->resolveMaxSizeBytes($user);
        $originalSize = (int) $file->getSize();
        $directory = 'editor-images/'.$user->id;
        $dimensions = $this->extractDimensions($file);

        if ($originalSize > $maxSizeBytes) {
            $optimized = $this->optimizeToLimit($file, $mime, $maxSizeBytes);

            if ($optimized === null) {
                throw ValidationException::withMessages([
                    'editorImage' => sprintf('Image could not be optimized below %dMB.', $this->toMegabytes($maxSizeBytes)),
                ]);
            }

            $filename = Str::uuid()->toString().'.'.$optimized['extension'];
            $path = $directory.'/'.$filename;

            Log::info('[EditorImageService] Storing optimized file', [
                'directory' => $directory,
                'filename' => $filename,
                'disk' => 'private',
                'originalSize' => $originalSize,
                'optimizedSize' => strlen($optimized['contents']),
            ]);

            $stored = Storage::disk('private')->put($path, $optimized['contents']);
            if (! $stored) {
                throw new \RuntimeException('Failed to store optimized editor image file.');
            }

            $dimensions = ['width' => $optimized['width'], 'height' => $optimized['height']];
        } else {
            $extension = $file->guessExtension() ?? $this->extensionForMime($mime);
            $filename = Str::uuid()->toString().'.'.$extension;

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
        }

        Log::info('[EditorImageService] File stored', ['path' => $path]);

        return Image::create([
            'user_id' => $user->id,
            'path' => $path,
            'disk' => 'private',
            'alt' => $file->getClientOriginalName(),
            'image_width' => $dimensions['width'],
            'image_height' => $dimensions['height'],
        ]);
    }

    private function resolveMaxSizeBytes(User $user): int
    {
        $default = (int) config('constellation.image_upload.max_bytes.default', 2 * 1024 * 1024);
        $vip = (int) config('constellation.image_upload.max_bytes.vip', 10 * 1024 * 1024);

        return $user->tier === Tier::VIP ? $vip : $default;
    }

    private function toMegabytes(int $bytes): int
    {
        return max(1, (int) round($bytes / 1024 / 1024));
    }

    /**
     * @return array{contents: string, extension: string, width: int, height: int}|null
     */
    private function optimizeToLimit(UploadedFile $file, string $mime, int $maxSizeBytes): ?array
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $realPath = $file->getRealPath();
        if (! is_string($realPath) || $realPath === '') {
            return null;
        }

        $source = $this->createImageResource($realPath, $mime);
        if (! $source instanceof GdImage) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scaleSteps = [1.0, 0.9, 0.8, 0.7, 0.6, 0.5, 0.4, 0.3, 0.25];
        $qualitySteps = $this->qualityStepsForMime($mime);

        foreach ($scaleSteps as $scale) {
            $targetWidth = max(1, (int) floor($width * $scale));
            $targetHeight = max(1, (int) floor($height * $scale));

            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            if (! $canvas instanceof GdImage) {
                continue;
            }

            $this->prepareCanvasForMime($canvas, $mime);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            foreach ($qualitySteps as $quality) {
                $encoded = $this->encodeImage($canvas, $mime, $quality);

                if ($encoded !== null && strlen($encoded) <= $maxSizeBytes) {
                    imagedestroy($canvas);
                    imagedestroy($source);

                    return [
                        'contents' => $encoded,
                        'extension' => $this->extensionForMime($mime),
                        'width' => $targetWidth,
                        'height' => $targetHeight,
                    ];
                }
            }

            imagedestroy($canvas);
        }

        imagedestroy($source);

        return null;
    }

    private function createImageResource(string $path, string $mime): ?GdImage
    {
        $resource = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false,
            'image/gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($path) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        return $resource instanceof GdImage ? $resource : null;
    }

    private function prepareCanvasForMime(GdImage $canvas, string $mime): void
    {
        if (! in_array($mime, ['image/png', 'image/webp', 'image/gif'], true)) {
            return;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = (int) imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, imagesx($canvas), imagesy($canvas), $transparent);
    }

    /** @return list<int> */
    private function qualityStepsForMime(string $mime): array
    {
        return match ($mime) {
            'image/jpeg' => [85, 75, 65, 55, 45, 35],
            'image/webp' => [80, 70, 60, 50, 40],
            'image/png' => [9, 9, 8, 8, 7, 6, 5],
            'image/gif' => [0],
            default => [75],
        };
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    private function encodeImage(GdImage $image, string $mime, int $quality): ?string
    {
        ob_start();

        $result = match ($mime) {
            'image/jpeg' => function_exists('imagejpeg') ? imagejpeg($image, null, $quality) : false,
            'image/png' => function_exists('imagepng') ? imagepng($image, null, $quality) : false,
            'image/gif' => function_exists('imagegif') ? imagegif($image) : false,
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, null, $quality) : false,
            default => false,
        };

        $contents = ob_get_clean();

        if (! $result || ! is_string($contents) || $contents === '') {
            return null;
        }

        return $contents;
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
