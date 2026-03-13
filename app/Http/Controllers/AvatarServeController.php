<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AvatarServeController extends Controller
{
    public function __invoke(User $user): Response
    {
        if (! $user->avatar_path || ! $user->avatar_disk) {
            abort(404);
        }

        $disk = Storage::disk($user->avatar_disk);

        if (! $disk->exists($user->avatar_path)) {
            abort(404);
        }

        return response($disk->get($user->avatar_path), 200, [
            'Content-Type' => $disk->mimeType($user->avatar_path),
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
