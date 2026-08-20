<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SurveySource: string implements HasLabel
{
    case Qr = 'qr';
    case Email = 'email';

    public function getLabel(): string
    {
        return match ($this) {
            self::Qr => 'QR at register',
            self::Email => 'Email',
        };
    }
}
