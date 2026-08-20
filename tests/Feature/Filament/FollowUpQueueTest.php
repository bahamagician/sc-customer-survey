<?php

use App\Enums\FollowUpStatus;
use App\Filament\Resources\SurveyResponses\Pages\FollowUpQueue;
use App\Filament\Resources\SurveyResponses\Pages\ListSurveyResponses;
use App\Models\SurveyResponse;
use App\Models\User;
use Livewire\Livewire;

test('guests cannot reach the follow-up queue', function () {
    $this->get(FollowUpQueue::getUrl())->assertRedirect();
});

test('the queue lists low scorers with an email and anyone who asked for follow-up', function () {
    $this->actingAs(User::factory()->create());

    $needsCall = SurveyResponse::factory()->create(['overall_score' => 3, 'email' => 'sad@example.com']);
    $askedHappy = SurveyResponse::factory()->create([
        'overall_score' => 9,
        'email' => 'happy@example.com',
        'follow_up_requested' => true,
    ]);
    $noEmail = SurveyResponse::factory()->create(['overall_score' => 2, 'email' => null]);
    $happyQuiet = SurveyResponse::factory()->create(['overall_score' => 9, 'email' => 'quiet@example.com']);
    $unanswered = SurveyResponse::factory()->create(['overall_score' => null, 'email' => 'none@example.com']);

    Livewire::test(FollowUpQueue::class)
        ->assertCanSeeTableRecords([$needsCall, $askedHappy])
        ->assertCanNotSeeTableRecords([$noEmail, $happyQuiet, $unanswered]);
});

test('the follow-up status can be updated inline', function () {
    $this->actingAs(User::factory()->create());

    $surveyResponse = SurveyResponse::factory()->create(['overall_score' => 4, 'email' => 'sad@example.com']);

    Livewire::test(FollowUpQueue::class)
        ->call('updateTableColumnState', 'follow_up_status', $surveyResponse->getKey(), FollowUpStatus::Contacted->value);

    expect($surveyResponse->refresh()->follow_up_status)->toBe(FollowUpStatus::Contacted);
});

test('the responses list renders for staff', function () {
    $this->actingAs(User::factory()->create());

    SurveyResponse::factory()->completed()->count(3)->create();

    Livewire::test(ListSurveyResponses::class)
        ->assertCanSeeTableRecords(SurveyResponse::all());
});
