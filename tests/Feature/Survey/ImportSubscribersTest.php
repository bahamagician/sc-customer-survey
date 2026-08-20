<?php

use App\Models\SurveySubscriber;

test('the import command seeds subscribers and emits the url csv', function () {
    $csv = tempnam(sys_get_temp_dir(), 'mailjet').'.csv';
    $output = tempnam(sys_get_temp_dir(), 'urls').'.csv';

    file_put_contents($csv, implode("\n", [
        'Name,Email',
        'Chris,  Chris@Example.COM ',
        ',broken-address',
        'Dana,dana@example.com',
        'Chris again,chris@example.com',
    ]));

    $this->artisan('survey:import-subscribers', ['path' => $csv, '--output' => $output])
        ->assertSuccessful();

    expect(SurveySubscriber::count())->toBe(2)
        ->and(SurveySubscriber::where('email', 'chris@example.com')->exists())->toBeTrue();

    $lines = array_map('str_getcsv', file($output));
    $chris = collect($lines)->firstWhere(0, 'chris@example.com');

    expect($lines[0])->toBe(['email', 'survey_url'])
        ->and($chris[1])->toContain('/survey?t=');

    unlink($csv);
    unlink($output);
});
