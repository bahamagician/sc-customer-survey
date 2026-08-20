<?php

namespace App\Survey;

use App\Enums\SurveySource;

/**
 * A single survey question hydrated from config/survey.php.
 */
readonly class Question
{
    /**
     * @param  string|array<string, string>  $label
     * @param  array<string, string>  $options
     * @param  array<string, list<string>>  $condition
     * @param  string|array<string, string>|null  $preamble
     */
    public function __construct(
        public string $key,
        public string $type,
        public string|array $label,
        public array $options = [],
        public ?string $column = null,
        public array $condition = [],
        public bool $optional = false,
        public ?string $hint = null,
        public string|array|null $preamble = null,
        public ?string $followUpLabel = null,
    ) {}

    /**
     * @param  array<string, mixed>  $definition
     */
    public static function fromConfig(string $key, array $definition): self
    {
        return new self(
            key: $key,
            type: $definition['type'],
            label: $definition['label'],
            options: $definition['options'] ?? [],
            column: $definition['column'] ?? null,
            condition: $definition['condition'] ?? [],
            optional: $definition['optional'] ?? false,
            hint: $definition['hint'] ?? null,
            preamble: $definition['preamble'] ?? null,
            followUpLabel: $definition['follow_up_label'] ?? null,
        );
    }

    public function labelFor(SurveySource $source): string
    {
        if (is_array($this->label)) {
            return $this->label[$source->value];
        }

        return $this->label;
    }

    public function preambleFor(SurveySource $source): ?string
    {
        if (is_array($this->preamble)) {
            return $this->preamble[$source->value] ?? null;
        }

        return $this->preamble;
    }

    /**
     * Whether this question should be shown given the answers so far.
     *
     * @param  array<string, mixed>  $answers
     */
    public function passesCondition(array $answers): bool
    {
        foreach ($this->condition as $key => $allowedValues) {
            if (! in_array($answers[$key] ?? null, $allowedValues, true)) {
                return false;
            }
        }

        return true;
    }
}
