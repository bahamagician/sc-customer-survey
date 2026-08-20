<?php

use App\Http\Controllers\Survey\SurveyEntryController;
use App\Http\Controllers\Survey\SurveyQuestionController;
use App\Http\Middleware\EnsureSurveySession;
use Illuminate\Support\Facades\Route;

Route::get('survey', SurveyEntryController::class)
    ->middleware('throttle:survey-entry')
    ->name('survey.entry');

Route::inertia('survey/thanks', 'survey/Thanks')->name('survey.thanks');

Route::middleware(EnsureSurveySession::class)->whereUuid('response')->group(function () {
    Route::get('survey/{response}/{question}', [SurveyQuestionController::class, 'show'])
        ->name('survey.question');

    Route::patch('survey/{response}/{question}', [SurveyQuestionController::class, 'update'])
        ->name('survey.answer');
});
