<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth.simple')]
#[Title('Welcome')]
class Welcome extends Component
{
    public string $name = '';

    public function mount(): void
    {
        $user = Auth::user();

        // If already completed welcome, redirect to canvas
        if ($user->first_login_at) {
            $this->redirect(route('canvas'));

            return;
        }

        $this->name = $user->name;
    }

    public function start(): void
    {
        $user = Auth::user();
        $user->first_login_at = now();
        $user->save();

        $this->redirect(route('canvas'));
    }

    public function render(): mixed
    {
        return view('livewire.welcome');
    }
}
