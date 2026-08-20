<?php

namespace App\Filament\Resources\SurveySubscribers;

use App\Filament\Resources\SurveySubscribers\Pages\ListSurveySubscribers;
use App\Filament\Resources\SurveySubscribers\Tables\SurveySubscribersTable;
use App\Models\SurveySubscriber;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SurveySubscriberResource extends Resource
{
    protected static ?string $model = SurveySubscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $modelLabel = 'subscriber';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return SurveySubscribersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurveySubscribers::route('/'),
        ];
    }
}
