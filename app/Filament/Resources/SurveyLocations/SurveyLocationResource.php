<?php

namespace App\Filament\Resources\SurveyLocations;

use App\Filament\Resources\SurveyLocations\Pages\ListSurveyLocations;
use App\Filament\Resources\SurveyLocations\Tables\SurveyLocationsTable;
use App\Models\SurveyLocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SurveyLocationResource extends Resource
{
    protected static ?string $model = SurveyLocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?string $modelLabel = 'location';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return SurveyLocationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurveyLocations::route('/'),
        ];
    }
}
