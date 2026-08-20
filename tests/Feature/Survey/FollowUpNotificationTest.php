<?php

use App\Models\SurveyResponse;
use App\Notifications\FollowUpRequested;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

test('marketing is notified when a respondent requests follow-up', function () {
    Notification::fake();

    $surveyResponse = SurveyResponse::factory()->create(['overall_score' => 3]);

    $this->withSession(['survey_response_id' => $surveyResponse->id])
        ->patch(route('survey.answer', [$surveyResponse, 'contact']), [
            'value' => ['name' => 'Chris', 'email' => 'chris@example.com', 'follow_up_requested' => true],
        ]);

    Notification::assertSentOnDemand(
        FollowUpRequested::class,
        fn (FollowUpRequested $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'marketing@sportscenter242.com'
            && $notification->response->is($surveyResponse),
    );
});

test('no notification is sent when follow-up is not requested', function () {
    Notification::fake();

    $surveyResponse = SurveyResponse::factory()->create();

    $this->withSession(['survey_response_id' => $surveyResponse->id])
        ->patch(route('survey.answer', [$surveyResponse, 'contact']), [
            'value' => ['name' => null, 'email' => 'draw@example.com', 'follow_up_requested' => false],
        ]);

    Notification::assertNothingSent();
});

test('resubmitting the contact step does not notify marketing twice', function () {
    Notification::fake();

    $surveyResponse = SurveyResponse::factory()->create();
    $payload = ['value' => ['name' => 'Chris', 'email' => 'chris@example.com', 'follow_up_requested' => true]];

    $withSession = $this->withSession(['survey_response_id' => $surveyResponse->id]);
    $withSession->patch(route('survey.answer', [$surveyResponse, 'contact']), $payload);
    $withSession->patch(route('survey.answer', [$surveyResponse, 'contact']), $payload);

    Notification::assertSentOnDemandTimes(FollowUpRequested::class, 1);
});

test('the notification email contains the essentials', function () {
    $surveyResponse = SurveyResponse::factory()->create([
        'name' => 'Chris',
        'email' => 'chris@example.com',
        'overall_score' => 3,
        'follow_up_requested' => true,
    ]);

    $mail = (new FollowUpRequested($surveyResponse))->toMail(new AnonymousNotifiable);

    expect($mail->subject)->toContain('Chris')
        ->and(implode(' ', array_map(strval(...), $mail->introLines)))->toContain('chris@example.com')
        ->and($mail->actionUrl)->toContain($surveyResponse->id);
});
