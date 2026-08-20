<?php

namespace App\Http\Controllers\Survey;

use App\Enums\SurveySource;
use App\Http\Controllers\Controller;
use App\Models\SurveyLocation;
use App\Models\SurveyResponse;
use App\Models\SurveySubscriber;
use App\Survey\AnswerWriter;
use App\Survey\QuestionSet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SurveyEntryController extends Controller
{
    /**
     * Resolve the channel from the entry URL, create or resume a
     * response, and redirect into the question flow. Requests carrying
     * neither a location nor a token are rejected — no unattributed
     * responses.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->filled('t')) {
            return $this->enterFromEmail($request, $request->string('t')->toString());
        }

        if ($request->query('src') === 'qr' && $request->filled('loc')) {
            return $this->enterFromQr($request, $request->string('loc')->toString());
        }

        abort(404);
    }

    private function enterFromEmail(Request $request, string $token): RedirectResponse
    {
        $subscriber = SurveySubscriber::query()->where('token', $token)->firstOrFail();

        $response = SurveyResponse::query()
            ->whereBelongsTo($subscriber, 'subscriber')
            ->latest()
            ->first();

        if ($response?->isCompleted()) {
            return redirect()->route('survey.thanks');
        }

        $response ??= SurveyResponse::query()->create([
            'source' => SurveySource::Email,
            'survey_subscriber_id' => $subscriber->id,
            'email' => AnswerWriter::normaliseEmail($subscriber->email),
        ]);

        return $this->resume($request, $response);
    }

    private function enterFromQr(Request $request, string $slug): RedirectResponse
    {
        $location = SurveyLocation::query()
            ->where('slug', $slug)
            ->where('active', true)
            ->firstOrFail();

        $sessionResponseId = $request->session()->get('survey_response_id');

        $existing = is_string($sessionResponseId)
            ? SurveyResponse::query()->find($sessionResponseId)
            : null;

        if ($existing !== null
            && ! $existing->isCompleted()
            && $existing->source === SurveySource::Qr
            && $existing->survey_location_id === $location->id) {
            return $this->resume($request, $existing);
        }

        $response = SurveyResponse::query()->create([
            'source' => SurveySource::Qr,
            'survey_location_id' => $location->id,
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);

        return $this->resume($request, $response);
    }

    /**
     * Point the session at this response and redirect to the first
     * question the respondent has not yet answered.
     */
    private function resume(Request $request, SurveyResponse $response): RedirectResponse
    {
        $request->session()->put('survey_response_id', $response->id);

        $set = QuestionSet::forChannel($response->source);

        $question = $response->last_question_key === null
            ? $set->first()
            : $set->after($response->last_question_key, $response->answers);

        if ($question === null) {
            return redirect()->route('survey.thanks');
        }

        return redirect()->route('survey.question', [$response, $question->key]);
    }
}
