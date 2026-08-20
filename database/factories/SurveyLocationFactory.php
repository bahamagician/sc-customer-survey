<?php

namespace Database\Factories;

use App\Enums\Brand;
use App\Models\SurveyLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyLocation>
 */
class SurveyLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->streetName();

        return [
            'slug' => str($name)->slug()->toString(),
            'brand' => fake()->randomElement(Brand::cases()),
            'name' => $name,
            'active' => true,
        ];
    }
}
