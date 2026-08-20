<?php

namespace App\Filament\Resources\SurveySubscribers\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SurveySubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('name')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('responses_count')
                    ->label('Responses')
                    ->counts('responses'),
                TextColumn::make('created_at')
                    ->label('Imported')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
