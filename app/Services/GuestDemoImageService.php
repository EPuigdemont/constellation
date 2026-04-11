<?php

declare(strict_types=1);

namespace App\Services;

class GuestDemoImageService
{
    /** @return list<array<string, mixed>> */
    public function galleryImages(): array
    {
        return array_map(function (array $image): array {
            return [
                'id' => $image['id'],
                'alt' => $image['alt'],
                'url' => $image['url'],
                'created_at' => __('Guest demo'),
                'is_demo' => true,
            ];
        }, $this->all());
    }

    /** @return list<array<string, mixed>> */
    public function cardsForContext(string $context): array
    {
        $cards = [];

        foreach ($this->all() as $image) {
            $layout = $image[$context] ?? null;

            if (! is_array($layout)) {
                continue;
            }

            $cards[] = [
                'id' => $image['id'],
                'type' => 'image',
                'title' => $image['title'],
                'preview' => $image['alt'],
                'mood' => 'plain',
                'color_override' => null,
                'x' => (float) ($layout['x'] ?? 0.0),
                'y' => (float) ($layout['y'] ?? 0.0),
                'z_index' => (int) ($layout['z_index'] ?? 0),
                'width' => isset($layout['width']) ? (float) $layout['width'] : null,
                'height' => isset($layout['height']) ? (float) $layout['height'] : null,
                'owner_id' => null,
                'owner_name' => 'Constellation Demo',
                'owner_username' => 'demo',
                'created_at' => null,
                'updated_at' => null,
                'parent_id' => null,
                'parent_type' => null,
                'children_count' => 0,
                'siblings_count' => 0,
                'tag_ids' => [],
                'image_url' => $image['url'],
                'is_hidden' => false,
                'is_demo' => true,
            ];
        }

        return $cards;
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        foreach ($this->all() as $image) {
            if ($image['id'] === $id) {
                return $image;
            }
        }

        return null;
    }

    /** @return list<array{id: string, path: string, url: string, title: string, alt: string, desktop?: array<string, mixed>, vision_board?: array<string, mixed>}> */
    private function all(): array
    {
        $images = config('constellation.guest_demo_images', []);

        if (! is_array($images)) {
            return [];
        }

        $normalized = [];

        foreach ($images as $image) {
            if (! is_array($image)) {
                continue;
            }

            $id = $image['id'] ?? null;
            $path = $image['path'] ?? null;

            if (! is_string($id) || $id === '' || ! is_string($path) || $path === '') {
                continue;
            }

            $normalized[] = [
                'id' => $id,
                'path' => $path,
                'url' => asset($path),
                'title' => (string) ($image['title'] ?? ''),
                'alt' => (string) ($image['alt'] ?? ''),
                'desktop' => is_array($image['desktop'] ?? null) ? $image['desktop'] : [],
                'vision_board' => is_array($image['vision_board'] ?? null) ? $image['vision_board'] : [],
            ];
        }

        return $normalized;
    }
}
