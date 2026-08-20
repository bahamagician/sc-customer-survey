<?php

use App\Filament\Resources\SurveySubscribers\Pages\ListSurveySubscribers;
use App\Models\SurveySubscriber;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('guests cannot reach the subscribers page', function () {
    $this->get(ListSurveySubscribers::getUrl())->assertRedirect();
});

test('a mailjet csv can be imported from the admin', function () {
    $this->actingAs(User::factory()->create());

    $csv = UploadedFile::fake()->createWithContent('mailjet.csv', implode("\n", [
        'Name,Email',
        'Chris,  Chris@Example.COM ',
        ',broken-address',
        'Dana,dana@example.com',
    ]));

    Livewire::test(ListSurveySubscribers::class)
        ->callAction('import', ['csv' => $csv])
        ->assertNotified();

    expect(SurveySubscriber::count())->toBe(2)
        ->and(SurveySubscriber::where('email', 'chris@example.com')->exists())->toBeTrue();
});

test('a real mailjet export shape imports and excludes unsubscribed contacts', function () {
    $this->actingAs(User::factory()->create());

    // MailJet exports open with a UTF-8 BOM, exactly as the real file does.
    $csv = UploadedFile::fake()->createWithContent('mailjet.csv', implode("\n", [
        "\u{FEFF}email,lastopen,lastclick,unsubscribed,subscribedat",
        '00avxryy00@gmail.com,,,f,2024-11-15T21:32:02',
        '08williamsjaydon@gmail.com,,,f,2024-09-20T17:50:24',
        'optedout@example.com,,,t,2024-01-01T00:00:00',
    ]));

    Livewire::test(ListSurveySubscribers::class)
        ->callAction('import', ['csv' => $csv]);

    expect(SurveySubscriber::count())->toBe(2)
        ->and(SurveySubscriber::where('email', 'optedout@example.com')->exists())->toBeFalse()
        ->and(SurveySubscriber::where('email', '00avxryy00@gmail.com')->sole()->name)->toBeNull();
});

test('re-importing keeps existing tokens stable', function () {
    $this->actingAs(User::factory()->create());

    $existing = SurveySubscriber::factory()->create(['email' => 'chris@example.com']);
    $token = $existing->token;

    $csv = UploadedFile::fake()->createWithContent('mailjet.csv', "Email\nchris@example.com\nnew@example.com");

    Livewire::test(ListSurveySubscribers::class)
        ->callAction('import', ['csv' => $csv]);

    expect(SurveySubscriber::count())->toBe(2)
        ->and($existing->refresh()->token)->toBe($token);
});

test('a csv without an email column fails with a notification and imports nothing', function () {
    $this->actingAs(User::factory()->create());

    $csv = UploadedFile::fake()->createWithContent('mailjet.csv', "Address\nchris@example.com");

    Livewire::test(ListSurveySubscribers::class)
        ->callAction('import', ['csv' => $csv])
        ->assertNotified('Import failed');

    expect(SurveySubscriber::count())->toBe(0);
});

test('the survey url csv can be downloaded from the admin', function () {
    $this->actingAs(User::factory()->create());

    SurveySubscriber::factory()->create(['email' => 'chris@example.com']);

    Livewire::test(ListSurveySubscribers::class)
        ->callAction('downloadUrls')
        ->assertFileDownloaded('survey_urls.csv');
});
