<?php

use App\Models\SurveyLocation;
use App\Models\SurveyResponse;

function startQrSurvey(): SurveyResponse
{
    SurveyLocation::factory()->create(['slug' => 'harbour-bay-sc']);

    test()->get(route('survey.entry', ['src' => 'qr', 'loc' => 'harbour-bay-sc']));

    return SurveyResponse::sole();
}

test('each answer persists immediately on step advance', function () {
    $surveyResponse = startQrSurvey();

    $this->patch(route('survey.answer', [$surveyResponse, 'departments']), ['value' => ['footwear']])
        ->assertRedirect(route('survey.question', [$surveyResponse, 'overall_score']));

    $this->patch(route('survey.answer', [$surveyResponse, 'overall_score']), ['value' => 9])
        ->assertRedirect(route('survey.question', [$surveyResponse, 'found_what_looking_for']));

    $this->patch(route('survey.answer', [$surveyResponse, 'found_what_looking_for']), ['value' => 4])
        ->assertRedirect(route('survey.question', [$surveyResponse, 'staff_knowledgeable']));

    // Three of sixteen answered and abandoned — all three are on the row.
    $surveyResponse->refresh();

    expect($surveyResponse->answers['departments'])->toBe(['footwear'])
        ->and($surveyResponse->overall_score)->toBe(9)
        ->and($surveyResponse->found_what_looking_for)->toBe(4)
        ->and($surveyResponse->completed_at)->toBeNull()
        ->and($surveyResponse->last_question_key)->toBe('found_what_looking_for');
});

test('promoted questions write to their column and not into answers', function () {
    $surveyResponse = startQrSurvey();

    $this->patch(route('survey.answer', [$surveyResponse, 'overall_score']), ['value' => 3]);

    $surveyResponse->refresh();

    expect($surveyResponse->overall_score)->toBe(3)
        ->and($surveyResponse->answers)->not->toHaveKey('overall_score');
});

test('refreshing mid-survey resumes at the next unanswered question without a new row', function () {
    $surveyResponse = startQrSurvey();

    $this->patch(route('survey.answer', [$surveyResponse, 'departments']), ['value' => ['footwear']]);

    // Re-scanning the QR code hits the entry URL again.
    $this->get(route('survey.entry', ['src' => 'qr', 'loc' => 'harbour-bay-sc']))
        ->assertRedirect(route('survey.question', [$surveyResponse, 'overall_score']));

    expect(SurveyResponse::count())->toBe(1);
});

test('the barrier question is skipped when purchase intent is as much or more', function () {
    $surveyResponse = startQrSurvey();

    $this->patch(route('survey.answer', [$surveyResponse, 'purchase_intent']), ['value' => 'as_much_as_intended'])
        ->assertRedirect(route('survey.question', [$surveyResponse, 'look_first']));
});

test('the barrier question is shown when purchase intent is less or nothing', function () {
    $surveyResponse = startQrSurvey();

    $this->patch(route('survey.answer', [$surveyResponse, 'purchase_intent']), ['value' => 'nothing'])
        ->assertRedirect(route('survey.question', [$surveyResponse, 'purchase_barrier']));
});

test('answering the final question completes the response', function () {
    $surveyResponse = startQrSurvey();

    $this->patch(route('survey.answer', [$surveyResponse, 'contact']), [
        'value' => ['name' => 'Chris', 'email' => 'chris@example.com', 'follow_up_requested' => true],
    ])->assertRedirect(route('survey.thanks'));

    $surveyResponse->refresh();

    expect($surveyResponse->completed_at)->not->toBeNull()
        ->and($surveyResponse->name)->toBe('Chris')
        ->and($surveyResponse->email)->toBe('chris@example.com')
        ->and($surveyResponse->follow_up_requested)->toBeTrue();
});

test('requesting follow-up without an email is rejected', function () {
    $surveyResponse = startQrSurvey();

    $this->from(route('survey.question', [$surveyResponse, 'contact']))
        ->patch(route('survey.answer', [$surveyResponse, 'contact']), [
            'value' => ['name' => 'Chris', 'email' => null, 'follow_up_requested' => true],
        ])
        ->assertInvalid(['value.email']);

    expect($surveyResponse->refresh()->completed_at)->toBeNull();
});

test('entering the draw without requesting follow-up leaves the flag false', function () {
    $surveyResponse = startQrSurvey();

    $this->patch(route('survey.answer', [$surveyResponse, 'contact']), [
        'value' => ['name' => null, 'email' => 'draw@example.com', 'follow_up_requested' => false],
    ]);

    expect($surveyResponse->refresh()->follow_up_requested)->toBeFalse();
});

test('a skipped optional text question stores null and advances', function () {
    $surveyResponse = startQrSurvey();

    $this->patch(route('survey.answer', [$surveyResponse, 'product_request']), ['value' => null])
        ->assertRedirect(route('survey.question', [$surveyResponse, 'improvement']));

    expect($surveyResponse->refresh()->completed_at)->toBeNull();
});

test('a question from the wrong channel returns 404', function () {
    $surveyResponse = startQrSurvey();

    $this->get(route('survey.question', [$surveyResponse, 'primary_location']))->assertNotFound();
    $this->patch(route('survey.answer', [$surveyResponse, 'primary_location']), ['value' => 'multiple'])
        ->assertNotFound();
});

test('a response url without the matching session returns 404', function () {
    $surveyResponse = SurveyResponse::factory()->create();

    $this->get(route('survey.question', [$surveyResponse, 'departments']))->assertNotFound();
    $this->patch(route('survey.answer', [$surveyResponse, 'departments']), ['value' => ['footwear']])
        ->assertNotFound();
});

test('the email primary location answer links the location when it maps to one', function () {
    $location = SurveyLocation::factory()->create(['slug' => 'sandyport-hw']);
    $surveyResponse = SurveyResponse::factory()->email()->create();

    $this->withSession(['survey_response_id' => $surveyResponse->id])
        ->patch(route('survey.answer', [$surveyResponse, 'primary_location']), ['value' => 'sandyport-hw']);

    $surveyResponse->refresh();

    expect($surveyResponse->survey_location_id)->toBe($location->id)
        ->and($surveyResponse->answers['primary_location'])->toBe('sandyport-hw');
});

test('the email primary location answer leaves the location null for more than one', function () {
    $surveyResponse = SurveyResponse::factory()->email()->create();

    $this->withSession(['survey_response_id' => $surveyResponse->id])
        ->patch(route('survey.answer', [$surveyResponse, 'primary_location']), ['value' => 'multiple']);

    $surveyResponse->refresh();

    expect($surveyResponse->survey_location_id)->toBeNull()
        ->and($surveyResponse->answers['primary_location'])->toBe('multiple');
});
