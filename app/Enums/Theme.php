<?php

declare(strict_types=1);

namespace App\Enums;

enum Theme: string
{
    case Summer = 'summer';
    case Love = 'love';
    case Breeze = 'breeze';
    case Night = 'night';
    case Cozy = 'cozy';

    public function label(): string
    {
        return match ($this) {
            self::Summer => 'Summer',
            self::Love => 'Love',
            self::Breeze => 'Breeze',
            self::Night => 'Night',
            self::Cozy => 'Cozy',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Summer => 'sun',
            self::Love => 'heart',
            self::Breeze => 'cloud',
            self::Night => 'moon',
            self::Cozy => 'fire',
        };
    }

    public function swatchColor(): string
    {
        return match ($this) {
            self::Summer => '#f9a825',
            self::Love => '#e91e63',
            self::Breeze => '#00acc1',
            self::Night => '#3f51b5',
            self::Cozy => '#795548',
        };
    }
}
