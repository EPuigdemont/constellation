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
];

