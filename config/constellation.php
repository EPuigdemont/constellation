<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Constellation App Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration values for the Constellation application, including
    | user account tier limits and other app-wide settings.
    |
    */

    'tiers' => [
        'guest' => [
            'images_total' => 20,
            'notes_per_day' => 10,
            'postits_per_day' => 20,
            'diary_entries_per_day' => 5,
            'reminders_per_day' => 5,
        ],
        'basic' => [
            'images_total' => 20,
            'notes_per_day' => 10,
            'postits_per_day' => 20,
            'diary_entries_per_day' => 5,
            'reminders_per_day' => 5,
        ],
        'premium' => [
            'images_total' => 200,
            'notes_per_day' => null, // unlimited
            'postits_per_day' => null, // unlimited
            'diary_entries_per_day' => null, // unlimited
            'reminders_per_day' => null, // unlimited
        ],
        'vip' => [
            'images_total' => null, // unlimited
            'notes_per_day' => null, // unlimited
            'postits_per_day' => null, // unlimited
            'diary_entries_per_day' => null, // unlimited
            'reminders_per_day' => null, // unlimited
        ],
    ],

    'automatic_themes' => [
        // Special dates override seasonal mapping (MM-DD => theme enum value).
        'special_dates' => [
            '02-14' => 'love',
            '10-31' => 'night',
            '12-24' => 'cozy',
            '12-25' => 'cozy',
            '12-26' => 'cozy',
        ],

        // Meteorological seasons by MM-DD ranges.
        'seasonal_ranges' => [
            ['start' => '03-01', 'end' => '05-31', 'theme' => 'spring'],
            ['start' => '06-01', 'end' => '08-31', 'theme' => 'summer'],
            ['start' => '09-01', 'end' => '11-30', 'theme' => 'autumn'],
            ['start' => '12-01', 'end' => '02-29', 'theme' => 'winter'],
        ],
    ],

    'guest_demo_images' => [
        [
            'id' => 'guest-demo-memory-wall',
            'path' => 'demo/guest/memory-wall.svg',
            'title' => 'Memory Wall',
            'alt' => 'A cozy memory wall full of pinned notes, photos, and flowers.',
            'desktop' => ['x' => 320.0, 'y' => 260.0, 'z_index' => 10, 'width' => 300.0, 'height' => 220.0],
            'vision_board' => ['x' => 380.0, 'y' => 300.0, 'z_index' => 10, 'width' => 320.0, 'height' => 240.0],
        ],
        [
            'id' => 'guest-demo-summer-postcard',
            'path' => 'demo/guest/summer-postcard.svg',
            'title' => 'Summer Postcard',
            'alt' => 'A bright summer postcard with ocean waves and handwritten notes.',
            'desktop' => ['x' => 700.0, 'y' => 180.0, 'z_index' => 11, 'width' => 280.0, 'height' => 210.0],
            'vision_board' => ['x' => 820.0, 'y' => 240.0, 'z_index' => 11, 'width' => 300.0, 'height' => 220.0],
        ],
        [
            'id' => 'guest-demo-night-sketchbook',
            'path' => 'demo/guest/night-sketchbook.svg',
            'title' => 'Night Sketchbook',
            'alt' => 'A dreamy sketchbook page with stars, constellations, and soft moonlight.',
            'desktop' => ['x' => 540.0, 'y' => 560.0, 'z_index' => 12, 'width' => 290.0, 'height' => 220.0],
            'vision_board' => ['x' => 620.0, 'y' => 560.0, 'z_index' => 12, 'width' => 310.0, 'height' => 230.0],
        ],
        [
            'id' => 'guest-demo-love-collage',
            'path' => 'demo/guest/love-collage.svg',
            'title' => 'Love Collage',
            'alt' => 'A romantic collage with hearts, pressed flowers, and tiny keepsakes.',
            'desktop' => ['x' => 940.0, 'y' => 470.0, 'z_index' => 13, 'width' => 280.0, 'height' => 210.0],
            'vision_board' => ['x' => 1050.0, 'y' => 500.0, 'z_index' => 13, 'width' => 300.0, 'height' => 220.0],
        ],
    ],
];
