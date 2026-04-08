<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AvatarService
{
    /**
     * Upload and store a new avatar for the user.
     */
    public function upload(User $user, UploadedFile $file): string
    {
        $this->deleteExisting($user);

        $disk = 'private';
        $path = $file->store("avatars/{$user->id}", $disk);
        if ($path === false) {
            throw new \RuntimeException('Failed to store avatar file.');
        }

        $user->update([
            'avatar_path' => $path,
            'avatar_disk' => $disk,
        ]);

        return $path;
    }

    /**
     * Delete the user's current avatar.
     */
    public function delete(User $user): void
    {
        $this->deleteExisting($user);

        $user->update([
            'avatar_path' => null,
            'avatar_disk' => null,
        ]);
    }

    /**
     * Delete existing avatar file from storage.
     */
    private function deleteExisting(User $user): void
    {
        if ($user->avatar_path && $user->avatar_disk) {
            Storage::disk($user->avatar_disk)->delete($user->avatar_path);
        }
    }
}
