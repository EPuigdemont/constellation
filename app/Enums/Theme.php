<?php

declare(strict_types=1);

namespace App\Enums;

enum Theme: string
{
    case Spring = 'spring';
    case Summer = 'summer';
    case Autumn = 'autumn';
    case Winter = 'winter';
    case Love = 'love';
    case Breeze = 'breeze';
    case Night = 'night';
    case Cozy = 'cozy';

    public function label(): string
    {
        return match ($this) {
            self::Spring => 'Spring',
            self::Summer => 'Summer',
            self::Autumn => 'Autumn',
            self::Winter => 'Winter',
            self::Love => 'Love',
            self::Breeze => 'Breeze',
            self::Night => 'Night',
            self::Cozy => 'Cozy',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Spring => 'sparkles',
            self::Summer => 'sun',
            self::Autumn => 'fire',
            self::Winter => 'cloud',
            self::Love => 'heart',
            self::Breeze => 'cloud',
            self::Night => 'moon',
            self::Cozy => 'fire',
        };
    }

    public function swatchColor(): string
    {
        return match ($this) {
            self::Spring => '#66bb6a',
            self::Summer => '#f9a825',
            self::Autumn => '#e65100',
            self::Winter => '#90caf9',
            self::Love => '#e91e63',
            self::Breeze => '#00acc1',
            self::Night => '#3f51b5',
            self::Cozy => '#795548',
        };
    }
}
