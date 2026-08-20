<?php

use App\Models\SurveyLocation;
use App\Models\SurveyResponse;

test('a honeypot submission is rejected and nothing is saved', function () {
    $surveyResponse = SurveyResponse::factory()->create();

    $this->withSession(['survey_response_id' => $surveyResponse->id])
        ->from(route('survey.question', [$surveyResponse, 'overall_score']))
        ->patch(route('survey.answer', [$surveyResponse, 'overall_score']), [
            'value' => 9,
            'contact_time' => 'bot-filled-this',
        ])
        ->assertInvalid(['contact_time']);

    expect($surveyResponse->refresh()->overall_score)->toBeNull();
});

test('email addresses are normalised on save', function () {
    $surveyResponse = SurveyResponse::factory()->create();

    $this->withSession(['survey_response_id' => $surveyResponse->id])
        ->patch(route('survey.answer', [$surveyResponse, 'contact']), [
            'value' => ['name' => null, 'email' => '  Chris@Example.COM '],
        ]);

    expect($surveyResponse->refresh()->email)->toBe('chris@example.com');
});

test('response creation is rate limited per ip and location', function () {
    SurveyLocation::factory()->create(['slug' => 'harbour-bay-sc']);

    for ($i = 0; $i < 30; $i++) {
        $this->get(route('survey.entry', ['src' => 'qr', 'loc' => 'harbour-bay-sc']))
            ->assertRedirect();

        // Each scan is a fresh visitor, not a resumed session.
        $this->flushSession();
    }

    $this->get(route('survey.entry', ['src' => 'qr', 'loc' => 'harbour-bay-sc']))
        ->assertTooManyRequests();
});

test('step saves are never rate limited', function () {
    $surveyResponse = SurveyResponse::factory()->create();

    $withSession = $this->withSession(['survey_response_id' => $surveyResponse->id]);

    for ($i = 0; $i < 40; $i++) {
        $withSession
            ->patch(route('survey.answer', [$surveyResponse, 'overall_score']), ['value' => 5])
            ->assertRedirect();
    }
});

test('an invalid option value is rejected', function () {
    $surveyResponse = SurveyResponse::factory()->create();

    $this->withSession(['survey_response_id' => $surveyResponse->id])
        ->from(route('survey.question', [$surveyResponse, 'departments']))
        ->patch(route('survey.answer', [$surveyResponse, 'departments']), [
            'value' => ['not_a_department'],
        ])
        ->assertInvalid(['value.0']);
});

test('selecting other on a barrier question requires the other text', function () {
    $surveyResponse = SurveyResponse::factory()->create([
        'answers' => ['purchase_intent' => 'nothing'],
    ]);

    $this->withSession(['survey_response_id' => $surveyResponse->id])
        ->from(route('survey.question', [$surveyResponse, 'purchase_barrier']))
        ->patch(route('survey.answer', [$surveyResponse, 'purchase_barrier']), [
            'value' => ['selected' => ['other'], 'other' => null],
        ])
        ->assertInvalid(['value.other']);

    $this->withSession(['survey_response_id' => $surveyResponse->id])
        ->patch(route('survey.answer', [$surveyResponse, 'purchase_barrier']), [
            'value' => ['selected' => ['price', 'other'], 'other' => 'No parking nearby'],
        ])
        ->assertRedirect(route('survey.question', [$surveyResponse, 'look_first']));

    expect($surveyResponse->refresh()->answers['purchase_barrier'])
        ->toEqualCanonicalizing(['selected' => ['price', 'other'], 'other' => 'No parking nearby']);
});
