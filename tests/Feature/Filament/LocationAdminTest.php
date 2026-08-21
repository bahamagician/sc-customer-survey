<?php

use App\Filament\Resources\SurveyLocations\Pages\ListSurveyLocations;
use App\Models\SurveyLocation;
use App\Models\User;
use App\Survey\LocationQrCode;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

test('guests cannot reach the locations page', function () {
    $this->get(ListSurveyLocations::getUrl())->assertRedirect();
});

test('the locations list renders for staff', function () {
    $this->actingAs(User::factory()->create());

    $locations = SurveyLocation::factory()->count(3)->create();

    Livewire::test(ListSurveyLocations::class)
        ->assertCanSeeTableRecords($locations);
});

test('the entry url encodes the qr channel and the location slug', function () {
    $location = SurveyLocation::factory()->create(['slug' => 'harbour-bay-sc']);

    expect(app(LocationQrCode::class)->entryUrl($location))
        ->toBe(route('survey.entry', ['src' => 'qr', 'loc' => 'harbour-bay-sc']));
});

test('the qr render is an svg document', function () {
    $location = SurveyLocation::factory()->create();

    $svg = app(LocationQrCode::class)->svg($location);

    expect($svg)->toContain('<svg')
        ->and($svg)->toContain('</svg>');
});

test('a single location qr can be downloaded', function () {
    $this->actingAs(User::factory()->create());

    $location = SurveyLocation::factory()->create(['slug' => 'sandyport-hw']);

    Livewire::test(ListSurveyLocations::class)
        ->callAction(TestAction::make('downloadQr')->table($location))
        ->assertFileDownloaded('qr-sandyport-hw.svg');
});

test('the zip action streams a download', function () {
    $this->actingAs(User::factory()->create());

    SurveyLocation::factory()->create();

    Livewire::test(ListSurveyLocations::class)
        ->callAction('downloadAllQr')
        ->assertFileDownloaded('survey-qr-codes.zip');
});

test('the zip contains active locations only', function () {
    SurveyLocation::factory()->create(['slug' => 'open-store', 'active' => true]);
    SurveyLocation::factory()->create(['slug' => 'closed-store', 'active' => false]);

    $path = tempnam(sys_get_temp_dir(), 'qr-test').'.zip';
    file_put_contents($path, app(LocationQrCode::class)->activeZip());

    $zip = new ZipArchive;
    $zip->open($path);
    $names = collect(range(0, $zip->numFiles - 1))->map(fn (int $i): string => $zip->getNameIndex($i));
    $zip->close();
    unlink($path);

    expect($names->all())->toContain('qr-open-store.svg')
        ->not->toContain('qr-closed-store.svg');
});

test('the active toggle persists', function () {
    $this->actingAs(User::factory()->create());

    $location = SurveyLocation::factory()->create(['active' => true]);

    Livewire::test(ListSurveyLocations::class)
        ->call('updateTableColumnState', 'active', $location->getKey(), false);

    expect($location->refresh()->active)->toBeFalse();
});
