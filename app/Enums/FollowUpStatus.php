<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FollowUpStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Contacted = 'contacted';
    case Resolved = 'resolved';
    case NoAction = 'no_action';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Contacted => 'Contacted',
            self::Resolved => 'Resolved',
            self::NoAction => 'No action needed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'danger',
            self::Contacted => 'warning',
            self::Resolved => 'success',
            self::NoAction => 'gray',
        };
    }
}
