<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Image;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Images')]
class ImagesGallery extends Component
{
    public function render(): View
    {
        $user = Auth::user();

        $images = Image::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Image $image): array => [
                'id' => $image->id,
                'alt' => $image->alt ?? '',
                'url' => route('images.serve', $image),
                'created_at' => $image->created_at?->format('d/m/Y H:i'),
            ]);

        return view('livewire.images-gallery', [
            'images' => $images,
        ]);
    }
}
