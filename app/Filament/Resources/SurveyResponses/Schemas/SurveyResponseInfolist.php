<?php

namespace App\Filament\Resources\SurveyResponses\Schemas;

use App\Enums\SurveySource;
use App\Models\SurveyResponse;
use App\Survey\Question;
use App\Survey\QuestionSet;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SurveyResponseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Response')
                    ->schema([
                        TextEntry::make('source')->badge(),
                        TextEntry::make('location.name')
                            ->label('Location')
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Started')
                            ->dateTime(),
                        TextEntry::make('completed_at')
                            ->dateTime()
                            ->placeholder('Abandoned mid-survey'),
                        TextEntry::make('name')->placeholder('—'),
                        TextEntry::make('email')->placeholder('—'),
                        IconEntry::make('follow_up_requested')
                            ->label('Asked for follow-up')
                            ->boolean(),
                        TextEntry::make('follow_up_status')
                            ->badge()
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make('Answers')
                    ->schema([
                        ...self::answerEntries(SurveySource::Qr),
                        ...self::answerEntries(SurveySource::Email),
                    ]),
            ]);
    }

    /**
     * One entry per question on the channel, labelled with the question
     * text and rendering option keys as their human labels. Entries for
     * the other channel are hidden per record.
     *
     * @return array<TextEntry>
     */
    private static function answerEntries(SurveySource $source): array
    {
        $set = QuestionSet::forChannel($source);
        $entries = [];

        foreach ($set->resolve(['purchase_intent' => 'nothing']) as $question) {
            if ($question->type === 'contact') {
                continue;
            }

            $entries[] = TextEntry::make("{$source->value}_{$question->key}")
                ->label($question->labelFor($source))
                ->state(fn (SurveyResponse $record): ?string => self::formatAnswer($record, $question))
                ->placeholder('Not answered')
                ->visible(fn (SurveyResponse $record): bool => $record->source === $source);
        }

        return $entries;
    }

    private static function formatAnswer(SurveyResponse $response, Question $question): ?string
    {
        $value = $question->column !== null
            ? $response->getAttribute($question->column)
            : ($response->answers[$question->key] ?? null);

        if ($value === null) {
            return null;
        }

        return match ($question->type) {
            'likert' => "{$value} / 5",
            'scale_1_10' => "{$value} / 10",
            'single_choice' => $question->options[$value] ?? (string) $value,
            'multi_select' => self::formatOptionList((array) $value, $question),
            'multi_select_other' => self::formatMultiSelectOther((array) $value, $question),
            default => (string) $value,
        };
    }

    /**
     * @param  array<mixed>  $keys
     */
    private static function formatOptionList(array $keys, Question $question): string
    {
        return implode(', ', array_map(
            fn (mixed $key): string => $question->options[$key] ?? (string) $key,
            array_values($keys),
        ));
    }

    /**
     * @param  array{selected?: list<string>, other?: string|null}  $value
     */
    private static function formatMultiSelectOther(array $value, Question $question): string
    {
        $labels = array_map(
            fn (string $key): string => $key === 'other'
                ? 'Other: '.($value['other'] ?? '')
                : ($question->options[$key] ?? $key),
            $value['selected'] ?? [],
        );

        return implode(', ', $labels);
    }
}
