<?php

use App\Concerns\ProfileValidationRules;
use App\Services\AvatarService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;
    use WithFileUploads;

    public string $name = '';
    public string $username = '';
    public string $email = '';
    public $avatar = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->username = Auth::user()->username ?? '';
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Upload a new avatar.
     */
    public function uploadAvatar(): void
    {
        $this->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $service = app(AvatarService::class);
        $service->upload(Auth::user(), $this->avatar);

        $this->avatar = null;
        $this->dispatch('avatar-updated');
    }

    /**
     * Remove the current avatar.
     */
    public function removeAvatar(): void
    {
        $service = app(AvatarService::class);
        $service->delete(Auth::user());

        $this->dispatch('avatar-updated');
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    {{-- Avatar --}}
    <x-pages::settings.layout :heading="__('Avatar')" :subheading="__('Upload a profile photo')">
        <div class="my-6 flex items-center gap-6">
            <div class="relative">
                @if(auth()->user()->avatarUrl())
                    <img src="{{ auth()->user()->avatarUrl() }}"
                         alt="{{ auth()->user()->name }}"
                         class="h-20 w-20 rounded-full object-cover ring-2 ring-zinc-200 dark:ring-zinc-700" />
                @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-zinc-200 text-xl font-bold text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                        {{ auth()->user()->initials() }}
                    </div>
                @endif
            </div>

            <div class="flex flex-col gap-2">
                <div>
                    <input type="file"
                           wire:model="avatar"
                           accept="image/jpeg,image/png,image/webp"
                           class="text-sm text-zinc-500 file:mr-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:text-zinc-400 dark:file:bg-zinc-700 dark:file:text-zinc-300" />
                    @error('avatar') <span class="mt-1 text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-2">
                    @if($avatar)
                        <flux:button size="sm" variant="primary" wire:click="uploadAvatar">
                            {{ __('Upload') }}
                        </flux:button>
                    @endif

                    @if(auth()->user()->avatarUrl())
                        <flux:button size="sm" variant="ghost" wire:click="removeAvatar" wire:confirm="{{ __('Remove your avatar?') }}">
                            {{ __('Remove') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        </div>

        <x-action-message on="avatar-updated">
            {{ __('Avatar updated.') }}
        </x-action-message>
    </x-pages::settings.layout>

    {{-- Profile Information --}}
    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name, username, and email address')" :show-nav="false">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <flux:input wire:model="username" :label="__('Username')" type="text" required autocomplete="username" placeholder="your-username" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
