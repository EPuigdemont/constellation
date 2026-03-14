<?php

declare(strict_types=1);

namespace App\Enums;

enum ReminderType: string
{
    case General = 'general';
    case MenstrualCycle = 'menstrual_cycle';
    case Ovulation = 'ovulation';

    public function label(): string
    {
        return match ($this) {
            self::General => __('General'),
            self::MenstrualCycle => __('Menstrual cycle'),
            self::Ovulation => __('Ovulation'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::General => 'bell',
            self::MenstrualCycle => 'heart',
            self::Ovulation => 'sparkles',
        };
    }
}
