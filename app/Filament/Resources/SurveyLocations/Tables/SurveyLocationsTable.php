<?php

namespace App\Filament\Resources\SurveyLocations\Tables;

use App\Models\SurveyLocation;
use App\Survey\LocationQrCode;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SurveyLocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->copyable(),
                TextColumn::make('brand')
                    ->badge(),
                TextColumn::make('responses_count')
                    ->label('Responses')
                    ->counts('responses'),
                ToggleColumn::make('active'),
            ])
            ->recordActions([
                Action::make('downloadQr')
                    ->label('Download QR')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->action(fn (SurveyLocation $record, LocationQrCode $qrCode): StreamedResponse => response()
                        ->streamDownload(
                            fn () => print $qrCode->svg($record),
                            "qr-{$record->slug}.svg",
                            ['Content-Type' => 'image/svg+xml'],
                        )),
            ]);
    }
}
