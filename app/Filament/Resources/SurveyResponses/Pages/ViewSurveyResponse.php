<?php

namespace App\Filament\Resources\SurveyResponses\Pages;

use App\Filament\Resources\SurveyResponses\SurveyResponseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSurveyResponse extends ViewRecord
{
    protected static string $resource = SurveyResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
