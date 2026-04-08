<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Image;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Images')]
class ImagesGallery extends Component
{
    public bool $showImageModal = false;

    public string $modalImageId = '';

    public string $modalImageUrl = '';

    public string $modalImageAlt = '';

    public function openImageModal(string $id, string $url, string $alt): void
    {
        $this->modalImageId = $id;
        $this->modalImageUrl = $url;
        $this->modalImageAlt = $alt;
        $this->showImageModal = true;
    }

    public function closeImageModal(): void
    {
        $this->showImageModal = false;
        $this->modalImageId = '';
        $this->modalImageUrl = '';
        $this->modalImageAlt = '';
    }

    public function deleteImage(string $id): void
    {
        $image = Image::where('user_id', Auth::id())->findOrFail($id);
        Gate::authorize('delete', $image);

        // Delete file from storage
        Storage::disk('private')->delete($image->path);
        $image->delete();

        $this->closeImageModal();
    }

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
