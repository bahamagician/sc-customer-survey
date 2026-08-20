<?php

namespace App\Survey;

use App\Models\SurveyLocation;
use App\Models\SurveyResponse;
use Illuminate\Support\Str;

/**
 * The single choke point for persisting an answer. Routes promoted
 * questions to their real column, contact details to the name/email
 * columns, and everything else into the answers JSON.
 */
class AnswerWriter
{
    public function write(SurveyResponse $response, Question $question, mixed $value): void
    {
        match ($question->type) {
            'contact' => $this->writeContact($response, $value),
            default => $this->writeAnswer($response, $question, $value),
        };

        $response->last_question_key = $question->key;
        $response->save();
    }

    private function writeAnswer(SurveyResponse $response, Question $question, mixed $value): void
    {
        if ($question->column !== null) {
            $response->setAttribute($question->column, $value);

            return;
        }

        // Mutate through the array cast — JSON `->` update syntax is not
        // portable to the sqlite test connection.
        $answers = $response->answers;
        $answers[$question->key] = $value;
        $response->answers = $answers;

        if ($question->key === 'primary_location' && is_string($value)) {
            $this->linkPrimaryLocation($response, $value);
        }
    }

    /**
     * @param  mixed  $value  expected shape: array{name?: string|null, email?: string|null, follow_up_requested?: bool}
     */
    private function writeContact(SurveyResponse $response, mixed $value): void
    {
        $name = is_array($value) ? ($value['name'] ?? null) : null;
        $email = is_array($value) ? ($value['email'] ?? null) : null;

        $response->name = is_string($name) && trim($name) !== '' ? trim($name) : null;
        $response->email = self::normaliseEmail($email);
        $response->follow_up_requested = is_array($value) && (bool) ($value['follow_up_requested'] ?? false);
    }

    /**
     * The raw choice always lives in answers for fidelity; the FK is set
     * additionally only when the choice maps to a real location. "I shop
     * at more than one" leaves the FK null.
     */
    private function linkPrimaryLocation(SurveyResponse $response, string $slug): void
    {
        $response->survey_location_id = SurveyLocation::query()
            ->where('slug', $slug)
            ->value('id');
    }

    public static function normaliseEmail(mixed $email): ?string
    {
        if (! is_string($email) || trim($email) === '') {
            return null;
        }

        return Str::lower(trim($email));
    }
}
