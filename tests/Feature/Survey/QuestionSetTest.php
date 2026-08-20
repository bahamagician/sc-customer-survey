<?php

use App\Enums\SurveySource;
use App\Survey\QuestionSet;

test('each channel resolves sixteen questions when all conditions match', function () {
    $qr = QuestionSet::forChannel(SurveySource::Qr);
    $email = QuestionSet::forChannel(SurveySource::Email);

    expect($qr->count(['purchase_intent' => 'nothing']))->toBe(16)
        ->and($email->count([]))->toBe(16);
});

test('the qr sequence excludes the barrier question until its condition matches', function () {
    $set = QuestionSet::forChannel(SurveySource::Qr);

    expect($set->count([]))->toBe(15)
        ->and($set->count(['purchase_intent' => 'as_much_as_intended']))->toBe(15)
        ->and($set->count(['purchase_intent' => 'less_than_intended']))->toBe(16);
});

test('email-only and qr-only questions never appear on the wrong channel', function () {
    $qr = QuestionSet::forChannel(SurveySource::Qr);
    $email = QuestionSet::forChannel(SurveySource::Email);

    foreach (['primary_location', 'last_shopped', 'staff_greet', 'usual_barrier'] as $emailOnly) {
        expect($qr->find($emailOnly))->toBeNull();
    }

    foreach (['greeted', 'thanked_at_checkout', 'purchase_intent', 'purchase_barrier'] as $qrOnly) {
        expect($email->find($qrOnly))->toBeNull();
    }
});

test('pooled questions are identically worded on both channels', function (string $key) {
    $qr = QuestionSet::forChannel(SurveySource::Qr)->find($key);
    $email = QuestionSet::forChannel(SurveySource::Email)->find($key);

    expect($qr->labelFor(SurveySource::Qr))->toBe($email->labelFor(SurveySource::Email));
})->with([
    'overall_score',
    'found_what_looking_for',
    'staff_knowledgeable',
    'staff_helpful',
    'store_clean',
    'prices_fair',
]);

test('the likert preamble matches each channel tense', function () {
    $question = QuestionSet::forChannel(SurveySource::Qr)->find('store_clean');

    expect($question->preambleFor(SurveySource::Qr))->toBe('During my visit today…')
        ->and($question->preambleFor(SurveySource::Email))->toBe('During my last visit…');
});

test('the departments label differs by channel', function () {
    $question = QuestionSet::forChannel(SurveySource::Qr)->find('departments');

    expect($question->labelFor(SurveySource::Qr))->toContain('today')
        ->and($question->labelFor(SurveySource::Email))->toContain('usually');
});

test('position reflects the resolved sequence when a conditional is skipped', function () {
    $set = QuestionSet::forChannel(SurveySource::Qr);

    // With the barrier hidden, look_first follows purchase_intent directly.
    expect($set->positionOf('look_first', ['purchase_intent' => 'more_than_intended']))->toBe(11)
        ->and($set->positionOf('look_first', ['purchase_intent' => 'nothing']))->toBe(12);
});
