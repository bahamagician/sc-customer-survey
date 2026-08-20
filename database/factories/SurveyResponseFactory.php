<?php

namespace Database\Factories;

use App\Enums\SurveySource;
use App\Models\SurveyLocation;
use App\Models\SurveyResponse;
use App\Models\SurveySubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyResponse>
 */
class SurveyResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => SurveySource::Qr,
            'survey_location_id' => SurveyLocation::factory(),
            'answers' => [],
        ];
    }

    public function qr(): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => SurveySource::Qr,
            'survey_location_id' => SurveyLocation::factory(),
            'survey_subscriber_id' => null,
        ]);
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => SurveySource::Email,
            'survey_location_id' => null,
            'survey_subscriber_id' => SurveySubscriber::factory(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'overall_score' => fake()->numberBetween(0, 10),
            'found_what_looking_for' => fake()->numberBetween(1, 5),
            'staff_knowledgeable' => fake()->numberBetween(1, 5),
            'staff_helpful' => fake()->numberBetween(1, 5),
            'store_clean' => fake()->numberBetween(1, 5),
            'prices_fair' => fake()->numberBetween(1, 5),
            'completed_at' => now(),
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes): array => [
            'overall_score' => fake()->numberBetween(0, 10),
            'completed_at' => null,
        ]);
    }
}
