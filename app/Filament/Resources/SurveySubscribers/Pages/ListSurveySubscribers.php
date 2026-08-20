<?php

namespace App\Filament\Resources\SurveySubscribers\Pages;

use App\Filament\Resources\SurveySubscribers\SurveySubscriberResource;
use App\Survey\SubscriberImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListSurveySubscribers extends ListRecords
{
    protected static string $resource = SurveySubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import MailJet CSV')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->schema([
                    FileUpload::make('csv')
                        ->label('MailJet export')
                        ->helperText('Needs an "email" column; a "name" column is picked up when present. Existing subscribers keep their token, so re-importing is always safe.')
                        ->disk('local')
                        ->directory('mailjet-imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->required(),
                ])
                ->action(function (array $data, SubscriberImporter $importer): void {
                    $path = Storage::disk('local')->path($data['csv']);

                    try {
                        $result = $importer->import($path);
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->title('Import failed')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    } finally {
                        Storage::disk('local')->delete($data['csv']);
                    }

                    $details = array_filter([
                        $result['unsubscribed'] > 0 ? "{$result['unsubscribed']} unsubscribed contacts excluded." : null,
                        $result['skipped'] > 0 ? "{$result['skipped']} rows skipped (missing or invalid email)." : null,
                    ]);

                    Notification::make()
                        ->title("Imported {$result['imported']} subscribers")
                        ->body($details === [] ? null : implode(' ', $details))
                        ->success()
                        ->send();
                }),
            Action::make('downloadUrls')
                ->label('Download survey URLs')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (SubscriberImporter $importer): StreamedResponse => response()->streamDownload(
                    function () use ($importer): void {
                        $handle = fopen('php://output', 'w');

                        if ($handle === false) {
                            return;
                        }

                        foreach ($importer->urlRows() as $row) {
                            fputcsv($handle, $row);
                        }

                        fclose($handle);
                    },
                    'survey_urls.csv',
                    ['Content-Type' => 'text/csv'],
                )),
        ];
    }
}
