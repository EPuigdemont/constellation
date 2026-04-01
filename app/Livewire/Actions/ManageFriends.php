<?php

declare(strict_types=1);

namespace App\Livewire\Actions;

use App\Services\FriendshipService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Friends')]
class ManageFriends extends Component
{
    public string $newFriendEmail = '';

    public string $errorMessage = '';

    public string $successMessage = '';

    public function addFriend(FriendshipService $service): void
    {
        $this->errorMessage = '';
        $this->successMessage = '';

        $email = trim($this->newFriendEmail);
        if ($email === '') {
            $this->errorMessage = __('Please enter an email address');

            return;
        }

        if ($email === Auth::user()->email) {
            $this->errorMessage = __('You cannot add yourself as a friend');

            return;
        }

        if ($service->sendFriendRequest(Auth::user(), $email)) {
            $this->successMessage = __('Friend request sent to :email', ['email' => $email]);
            $this->newFriendEmail = '';
        } else {
            $this->errorMessage = __('Could not send friend request. User may not exist or request already sent.');
        }
    }

    public function acceptRequest(FriendshipService $service, string $friendshipId): void
    {
        if ($service->acceptFriendRequest(Auth::user(), $friendshipId)) {
            $this->successMessage = __('Friend request accepted');
            $this->dispatch('refresh');
        }
    }

    public function rejectRequest(FriendshipService $service, string $friendshipId): void
    {
        if ($service->rejectFriendRequest(Auth::user(), $friendshipId)) {
            $this->successMessage = __('Friend request rejected');
            $this->dispatch('refresh');
        }
    }

    public function removeFriend(FriendshipService $service, string $friendId): void
    {
        if ($service->removeFriend(Auth::user(), $friendId)) {
            $this->successMessage = __('Friend removed');
            $this->dispatch('refresh');
        }
    }

    public function render(): View
    {
        $service = app(FriendshipService::class);
        $user = Auth::user();

        return view('livewire.actions.manage-friends', [
            'friends' => $service->getFriendsForUser($user),
            'pendingRequests' => $service->getPendingRequests($user),
            'pendingOutgoing' => $service->getPendingOutgoing($user),
        ]);
    }
}

