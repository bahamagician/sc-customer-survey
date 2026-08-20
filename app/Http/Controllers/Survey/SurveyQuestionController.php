<?php

namespace App\Http\Controllers\Survey;

use App\Http\Controllers\Controller;
use App\Http\Requests\Survey\StoreSurveyAnswerRequest;
use App\Models\SurveyResponse;
use App\Notifications\FollowUpRequested;
use App\Survey\AnswerWriter;
use App\Survey\Question;
use App\Survey\QuestionSet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class SurveyQuestionController extends Controller
{
    public function show(SurveyResponse $response, string $question): Response
    {
        $set = QuestionSet::forChannel($response->source);
        $current = $set->find($question);

        abort_if($current === null || ! $current->passesCondition($response->answers), 404);

        $resolved = $set->resolve($response->answers);
        $position = $set->positionOf($current->key, $response->answers);

        return Inertia::render('survey/Question', [
            'responseId' => $response->id,
            'question' => [
                'key' => $current->key,
                'type' => $current->type,
                'preamble' => $current->preambleFor($response->source),
                'label' => $current->labelFor($response->source),
                'options' => $current->options,
                'optional' => $current->optional,
                'hint' => $current->hint,
                'followUpLabel' => $current->followUpLabel,
            ],
            'value' => $this->currentValue($response, $current),
            'progress' => [
                'position' => $position,
                'total' => count($resolved),
            ],
            'previousKey' => $position > 1 ? $resolved[$position - 2]->key : null,
        ]);
    }

    public function update(
        StoreSurveyAnswerRequest $request,
        AnswerWriter $writer,
        SurveyResponse $response,
    ): RedirectResponse {
        $question = $request->question();

        $writer->write($response, $question, $request->validated('value'));

        // Notify only on the transition, so going back and resubmitting
        // the contact step never emails marketing twice.
        if ($response->wasChanged('follow_up_requested') && $response->follow_up_requested) {
            Notification::route('mail', config('survey.follow_up_notification_email'))
                ->notify(new FollowUpRequested($response));
        }

        $next = QuestionSet::forChannel($response->source)
            ->after($question->key, $response->answers);

        if ($next !== null) {
            return redirect()->route('survey.question', [$response, $next->key]);
        }

        if ($response->completed_at === null) {
            $response->completed_at = now();
            $response->save();
        }

        return redirect()->route('survey.thanks');
    }

    /**
     * The previously saved answer, so Back never loses data.
     */
    private function currentValue(SurveyResponse $response, Question $question): mixed
    {
        if ($question->type === 'contact') {
            return [
                'name' => $response->name,
                'email' => $response->email,
                'follow_up_requested' => $response->follow_up_requested,
            ];
        }

        if ($question->column !== null) {
            return $response->getAttribute($question->column);
        }

        return $response->answers[$question->key] ?? null;
    }
}
