<?php

declare(strict_types=1);

namespace App\Enums;

enum Mood: string
{
    case Spring = 'spring';
    case Summer = 'summer';
    case Autumn = 'autumn';
    case Winter = 'winter';
    case Love = 'love';
    case Breeze = 'breeze';
    case Night = 'night';
    case Cozy = 'cozy';
    case Plain = 'plain';
    case Custom = 'custom';
}
