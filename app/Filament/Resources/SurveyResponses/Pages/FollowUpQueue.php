<?php

namespace App\Filament\Resources\SurveyResponses\Pages;

use App\Enums\FollowUpStatus;
use App\Filament\Resources\SurveyResponses\SurveyResponseResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The service-recovery queue: everyone who explicitly asked for a
 * follow-up, plus low scorers who left an email even without asking.
 * The highest-value screen in the admin.
 */
class FollowUpQueue extends ListRecords
{
    protected static string $resource = SurveyResponseResource::class;

    protected static ?string $title = 'Follow-up Queue';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->whereNotNull('email')
                ->where(fn (Builder $query): Builder => $query
                    ->where('follow_up_requested', true)
                    ->orWhere('overall_score', '<=', 6)))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('overall_score')
                    ->label('Score')
                    ->badge()
                    ->color(fn (?int $state): string => $state !== null && $state <= 6 ? 'danger' : 'gray')
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('follow_up_requested')
                    ->label('Asked for follow-up')
                    ->boolean(),
                TextColumn::make('name')
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->copyable(),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->placeholder('—'),
                SelectColumn::make('follow_up_status')
                    ->label('Status')
                    ->options(FollowUpStatus::class)
                    ->placeholder('Not started'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
