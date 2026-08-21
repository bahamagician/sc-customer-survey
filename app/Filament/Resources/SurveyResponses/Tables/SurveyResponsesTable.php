<?php

namespace App\Filament\Resources\SurveyResponses\Tables;

use App\Enums\SurveySource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SurveyResponsesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('source')
                    ->badge(),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->placeholder('—'),
                TextColumn::make('overall_score')
                    ->label('Score')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state <= 6 => 'danger',
                        $state <= 8 => 'warning',
                        default => 'success',
                    })
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('completed')
                    ->label('Completed')
                    ->boolean()
                    ->state(fn ($record): bool => $record->isCompleted()),
                TextColumn::make('name')
                    ->placeholder('—')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->options(SurveySource::class),
                SelectFilter::make('survey_location_id')
                    ->label('Location')
                    ->relationship('location', 'name'),
                TernaryFilter::make('completed')
                    ->label('Completed')
                    ->nullable()
                    ->attribute('completed_at')
                    ->trueLabel('Completed')
                    ->falseLabel('Partial')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('completed_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('completed_at'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                Filter::make('created_between')
                    ->schema([
                        DatePicker::make('created_from')->label('From'),
                        DatePicker::make('created_until')->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['created_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['created_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
                SelectFilter::make('score')
                    ->label('Score range')
                    ->options([
                        'low' => '0–6 (needs attention)',
                        'mid' => '7–8',
                        'high' => '9–10',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $query, string $range): Builder => match ($range) {
                            'low' => $query->whereBetween('overall_score', [0, 6]),
                            'mid' => $query->whereBetween('overall_score', [7, 8]),
                            'high' => $query->whereBetween('overall_score', [9, 10]),
                            default => $query,
                        })),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
