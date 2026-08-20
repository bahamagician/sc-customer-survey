<?php

namespace App\Survey;

use App\Enums\SurveySource;
use InvalidArgumentException;

/**
 * The ordered question sequence for one channel, with conditional
 * questions resolved against the answers given so far.
 */
class QuestionSet
{
    /**
     * @param  list<Question>  $questions
     */
    private function __construct(
        public readonly SurveySource $source,
        private readonly array $questions,
    ) {}

    public static function forChannel(SurveySource $source): self
    {
        /** @var array<string, array<string, mixed>> $definitions */
        $definitions = config('survey.questions');

        /** @var list<string> $sequence */
        $sequence = config("survey.sequences.{$source->value}");

        $questions = array_map(
            fn (string $key): Question => Question::fromConfig($key, $definitions[$key]),
            $sequence,
        );

        return new self($source, $questions);
    }

    /**
     * The questions this respondent will actually see, given their
     * answers so far. A conditional whose controlling question is
     * unanswered is excluded until that answer arrives.
     *
     * @param  array<string, mixed>  $answers
     * @return list<Question>
     */
    public function resolve(array $answers): array
    {
        return array_values(array_filter(
            $this->questions,
            fn (Question $question): bool => $question->passesCondition($answers),
        ));
    }

    /**
     * Find a question by key regardless of conditions. Null when the
     * key does not exist on this channel.
     */
    public function find(string $key): ?Question
    {
        foreach ($this->questions as $question) {
            if ($question->key === $key) {
                return $question;
            }
        }

        return null;
    }

    public function first(): Question
    {
        return $this->questions[0];
    }

    /**
     * The question following the given key, skipping any conditional
     * that does not match the answers so far. Null at the end.
     *
     * @param  array<string, mixed>  $answers
     */
    public function after(string $key, array $answers): ?Question
    {
        $resolved = $this->resolve($answers);

        foreach ($resolved as $index => $question) {
            if ($question->key === $key) {
                return $resolved[$index + 1] ?? null;
            }
        }

        throw new InvalidArgumentException("Question [{$key}] is not part of the resolved {$this->source->value} sequence.");
    }

    /**
     * One-based position of the question in the resolved sequence,
     * for the progress bar.
     *
     * @param  array<string, mixed>  $answers
     */
    public function positionOf(string $key, array $answers): int
    {
        foreach ($this->resolve($answers) as $index => $question) {
            if ($question->key === $key) {
                return $index + 1;
            }
        }

        throw new InvalidArgumentException("Question [{$key}] is not part of the resolved {$this->source->value} sequence.");
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    public function count(array $answers): int
    {
        return count($this->resolve($answers));
    }
}
