<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tier;
use App\Models\User;
use Illuminate\Support\Str;

class GuestAccountService
{
    /**
     * Create a new guest user account that expires after 24 hours.
     */
    public function createGuestUser(): User
    {
        $guestUsername = 'guest_'.Str::random(12);
        $guestEmail = 'guest_'.Str::random(20).'@guest.constellation';

        return User::create([
            'name' => 'Guest User',
            'username' => $guestUsername,
            'email' => $guestEmail,
            'password' => Str::random(32),
            'tier' => Tier::Guest,
            'theme' => 'summer',
            'language' => app()->getLocale(),
            'desktop_zoom' => 1.0,
            'vision_board_zoom' => 1.0,
            'diary_display_mode' => 'infinite',
            'automatic_themes' => false,
            'guest_created_at' => now(),
            'guest_expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Convert a guest user to a full user account.
     * Guest account data is preserved.
     *
     * @param  array{name: string, username: string, email: string, password: string}  $userData
     */
    public function convertGuestToFullUser(User $user, array $userData): User
    {
        if (! $user->isGuest()) {
            throw new \InvalidArgumentException('User is not a guest account.');
        }

        $user->update([
            'name' => $userData['name'],
            'username' => $userData['username'],
            'email' => $userData['email'],
            'password' => $userData['password'],
            'tier' => Tier::Basic,
            'guest_expires_at' => null,
            'guest_created_at' => null,
        ]);

        return $user->refresh();
    }

    /**
     * Clean up expired guest accounts.
     * Optionally delete their data or keep it for a period.
     */
    public function cleanupExpiredGuests(bool $hardDelete = false): int
    {
        $expiredGuests = User::where('tier', Tier::Guest->value)
            ->where('guest_expires_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expiredGuests as $guest) {
            if ($hardDelete) {
                // Hard delete: remove all associated data
                $this->deleteGuestAndData($guest);
            } else {
                // Soft cleanup: mark data as deleted but keep records
                $this->softDeleteGuestData($guest);
            }
            $count++;
        }

        return $count;
    }

    /**
     * Delete a guest user and all their associated data.
     */
    private function deleteGuestAndData(User $guest): void
    {
        // Delete diary entries
        $guest->diaryEntries()->delete();

        // Delete notes
        $guest->notes()->delete();

        // Delete post-its
        $guest->postits()->delete();

        // Delete tags
        $guest->tags()->delete();

        // Delete entity positions
        $guest->entityPositions()->delete();

        // Delete important dates
        $guest->importantDates()->delete();

        // Delete reminders
        $guest->reminders()->delete();

        // Delete the user
        $guest->delete();
    }

    /**
     * Soft delete guest data by marking entities as deleted.
     */
    private function softDeleteGuestData(User $guest): void
    {
        $guest->diaryEntries()->forceDelete();
        $guest->notes()->forceDelete();
        $guest->postits()->forceDelete();
        $guest->tags()->forceDelete();
        $guest->entityPositions()->forceDelete();
        $guest->importantDates()->forceDelete();
        $guest->reminders()->forceDelete();
        $guest->delete();
    }
}
