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
];

