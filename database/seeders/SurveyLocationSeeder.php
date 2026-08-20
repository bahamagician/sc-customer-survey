<?php

namespace Database\Seeders;

use App\Enums\Brand;
use App\Models\SurveyLocation;
use Illuminate\Database\Seeder;

class SurveyLocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['slug' => 'harbour-bay-sc', 'brand' => Brand::SportsCenter, 'name' => 'The Sports Center — Harbour Bay'],
            ['slug' => 'marathon-mall-sc', 'brand' => Brand::SportsCenter, 'name' => 'The Sports Center — Marathon Mall'],
            ['slug' => 'sandyport-sc', 'brand' => Brand::SportsCenter, 'name' => 'The Sports Center — Sandyport'],
            ['slug' => 'harbour-bay-hw', 'brand' => Brand::HeatWave, 'name' => 'HeatWave — Harbour Bay'],
            ['slug' => 'sandyport-hw', 'brand' => Brand::HeatWave, 'name' => 'HeatWave — Sandyport'],
            ['slug' => 'harbour-bay-outlet', 'brand' => Brand::Outlet, 'name' => 'The Outlet — Harbour Bay'],
        ];

        foreach ($locations as $location) {
            SurveyLocation::query()->updateOrCreate(
                ['slug' => $location['slug']],
                $location,
            );
        }
    }
}
