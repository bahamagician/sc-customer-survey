<?php

use App\Enums\SurveySource;
use App\Models\SurveyLocation;
use App\Models\SurveyResponse;
use App\Models\SurveySubscriber;

test('entry with a valid location slug creates a qr response', function () {
    $location = SurveyLocation::factory()->create(['slug' => 'harbour-bay-sc']);

    $response = $this->get(route('survey.entry', ['src' => 'qr', 'loc' => 'harbour-bay-sc']));

    $surveyResponse = SurveyResponse::sole();

    expect($surveyResponse->source)->toBe(SurveySource::Qr)
        ->and($surveyResponse->survey_location_id)->toBe($location->id)
        ->and($surveyResponse->survey_subscriber_id)->toBeNull();

    $response->assertRedirect(route('survey.question', [$surveyResponse, 'departments']));
});

test('entry with a valid token creates an email response with the email prefilled', function () {
    $subscriber = SurveySubscriber::factory()->create(['email' => 'chris@example.com']);

    $response = $this->get(route('survey.entry', ['t' => $subscriber->token]));

    $surveyResponse = SurveyResponse::sole();

    expect($surveyResponse->source)->toBe(SurveySource::Email)
        ->and($surveyResponse->survey_subscriber_id)->toBe($subscriber->id)
        ->and($surveyResponse->survey_location_id)->toBeNull()
        ->and($surveyResponse->email)->toBe('chris@example.com');

    $response->assertRedirect(route('survey.question', [$surveyResponse, 'primary_location']));
});

test('entry with neither a location nor a token returns 404', function () {
    $this->get(route('survey.entry'))->assertNotFound();
});

test('entry with an unknown location slug returns 404', function () {
    $this->get(route('survey.entry', ['src' => 'qr', 'loc' => 'nowhere']))->assertNotFound();
});

test('entry with an inactive location returns 404', function () {
    SurveyLocation::factory()->create(['slug' => 'closed-store', 'active' => false]);

    $this->get(route('survey.entry', ['src' => 'qr', 'loc' => 'closed-store']))->assertNotFound();
});

test('entry with an unknown token returns 404', function () {
    $this->get(route('survey.entry', ['t' => str_repeat('x', 32)]))->assertNotFound();
});

test('revisiting a token after completing redirects to thanks without a second row', function () {
    $subscriber = SurveySubscriber::factory()->create();
    SurveyResponse::factory()->completed()->create([
        'source' => SurveySource::Email,
        'survey_location_id' => null,
        'survey_subscriber_id' => $subscriber->id,
    ]);

    $this->get(route('survey.entry', ['t' => $subscriber->token]))
        ->assertRedirect(route('survey.thanks'));

    expect(SurveyResponse::count())->toBe(1);
});

test('revisiting a token mid-survey resumes the existing response', function () {
    $subscriber = SurveySubscriber::factory()->create();
    $surveyResponse = SurveyResponse::factory()->create([
        'source' => SurveySource::Email,
        'survey_location_id' => null,
        'survey_subscriber_id' => $subscriber->id,
        'answers' => ['primary_location' => 'multiple'],
        'last_question_key' => 'primary_location',
    ]);

    $this->get(route('survey.entry', ['t' => $subscriber->token]))
        ->assertRedirect(route('survey.question', [$surveyResponse, 'departments']));

    expect(SurveyResponse::count())->toBe(1);
});
