<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Brand: string implements HasLabel
{
    case SportsCenter = 'sports_center';
    case HeatWave = 'heatwave';
    case Outlet = 'outlet';

    public function getLabel(): string
    {
        return match ($this) {
            self::SportsCenter => 'The Sports Center',
            self::HeatWave => 'HeatWave',
            self::Outlet => 'The Outlet',
        };
    }
}
