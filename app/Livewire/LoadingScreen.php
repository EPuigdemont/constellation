<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth.simple')]
#[Title('Loading...')]
class LoadingScreen extends Component
{
    public string $message = '';

    private const array MESSAGES = [
        'Gathering your stars...',
        'Connecting your constellations...',
        'Painting the sky...',
        'Aligning the cosmos...',
        'Lighting up your universe...',
        'Arranging your memories...',
        'Sprinkling stardust...',
        'Unfolding your world...',
    ];

    public function mount(): void
    {
        $this->message = self::MESSAGES[array_rand(self::MESSAGES)];
    }

    public function render(): mixed
    {
        return view('livewire.loading-screen');
    }
}
