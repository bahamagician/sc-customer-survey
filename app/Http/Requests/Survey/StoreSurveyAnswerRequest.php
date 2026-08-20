<?php

namespace App\Http\Requests\Survey;

use App\Models\SurveyResponse;
use App\Survey\Question;
use App\Survey\QuestionSet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSurveyAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'value.name' => 'name',
            'value.email' => 'email',
            'value.other' => 'details',
        ];
    }

    /**
     * Rules are derived from the question definition in config/survey.php.
     * The contact_time field is a honeypot — humans never see it, so any
     * submitted value rejects the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contact_time' => ['prohibited'],
            ...$this->valueRules($this->question()),
        ];
    }

    public function question(): Question
    {
        $response = $this->route('response');
        abort_unless($response instanceof SurveyResponse, 404);

        $question = QuestionSet::forChannel($response->source)
            ->find((string) $this->route('question'));

        abort_if($question === null, 404);

        return $question;
    }

    /**
     * @return array<string, mixed>
     */
    private function valueRules(Question $question): array
    {
        $optionKeys = array_keys($question->options);

        return match ($question->type) {
            'likert' => ['value' => ['required', 'integer', 'between:1,5']],
            'scale_1_10' => ['value' => ['required', 'integer', 'between:1,10']],
            'single_choice' => ['value' => ['required', 'string', Rule::in($optionKeys)]],
            'multi_select' => [
                'value' => ['required', 'array', 'min:1'],
                'value.*' => ['string', Rule::in($optionKeys)],
            ],
            'multi_select_other' => [
                'value' => ['required', 'array'],
                'value.selected' => ['required', 'array', 'min:1'],
                'value.selected.*' => ['string', Rule::in([...$optionKeys, 'other'])],
                'value.other' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::requiredIf(in_array('other', (array) $this->input('value.selected', []), true)),
                ],
            ],
            'text' => ['value' => ['nullable', 'string', 'max:2000']],
            'contact' => [
                'value' => ['required', 'array'],
                'value.name' => ['nullable', 'string', 'max:255'],
                'value.email' => [
                    'nullable',
                    'email',
                    'max:255',
                    Rule::requiredIf($this->boolean('value.follow_up_requested')),
                ],
                'value.follow_up_requested' => ['boolean'],
            ],
            default => abort(404),
        };
    }
}
