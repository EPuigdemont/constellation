<?php

declare(strict_types=1);

namespace App\Enums;

enum Tier: string
{
    case Basic = 'basic';
    case Premium = 'premium';
    case VIP = 'vip';

    public function label(): string
    {
        return match ($this) {
            self::Basic => 'Basic',
            self::Premium => 'Premium',
            self::VIP => 'VIP',
        };
    }
}
