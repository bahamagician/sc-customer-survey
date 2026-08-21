<?php

namespace App\Filament\Resources\SurveyLocations\Pages;

use App\Filament\Resources\SurveyLocations\SurveyLocationResource;
use App\Survey\LocationQrCode;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListSurveyLocations extends ListRecords
{
    protected static string $resource = SurveyLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadAllQr')
                ->label('Download all QR codes')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (LocationQrCode $qrCode): StreamedResponse => response()
                    ->streamDownload(
                        fn () => print $qrCode->activeZip(),
                        'survey-qr-codes.zip',
                        ['Content-Type' => 'application/zip'],
                    )),
        ];
    }
}
