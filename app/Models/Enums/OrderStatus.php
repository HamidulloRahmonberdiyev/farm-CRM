<?php

namespace App\Models\Enums;

use Filament\Support\Contracts\HasLabel;

enum OrderStatus: int implements HasLabel
{
    case ACTIVE = 1;
    case STOPPED = 2;
    case DISABLED = 0;

    public static function valueOf($value): ?OrderStatus
    {
        if ($value === null) return null;
        if ($value instanceof self) return $value;
        foreach (self::cases() as $case) {
            if ($case->value === $value) return $case;
        }
        return null;
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'Faol',
            self::STOPPED => 'To\'xtatilgan',
            self::DISABLED => 'Tugatilgan',
        };
    }
}
