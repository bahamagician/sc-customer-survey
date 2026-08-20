<?php

namespace Database\Factories;

use App\Models\SurveySubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SurveySubscriber>
 */
class SurveySubscriberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'token' => Str::random(32),
            'name' => fake()->boolean(70) ? fake()->name() : null,
        ];
    }
}
