<?php

use App\Http\Controllers\Diagnostic\DiagnosticController;
use App\Http\Controllers\Diagnostic\HistoryController;
use App\Http\Controllers\Information\CategoryController;
use App\Http\Controllers\Information\ContentController;
use App\Http\Controllers\Information\InformationController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

/*
|--------------------------------------------------------------------------
| Public information module (§5.2)
|--------------------------------------------------------------------------
*/
Route::get('informations', [InformationController::class, 'index'])->name('informations.index');
Route::get('informations/{category:slug}', [CategoryController::class, 'show'])
    ->name('informations.category');
Route::scopeBindings()->get('informations/{category:slug}/{content:slug}', [ContentController::class, 'show'])
    ->name('informations.content');
Route::get('pages/{content:slug}', [ContentController::class, 'page'])->name('pages.show');

/*
|--------------------------------------------------------------------------
| Public diagnostic module (§5.3)
|--------------------------------------------------------------------------
| Anonymous visitors can run a diagnostic; only authenticated users get a
| persisted row + history.
*/
Route::get('diagnostic', [DiagnosticController::class, 'index'])->name('diagnostic.index');

// History routes must be declared BEFORE the `{questionnaire:slug}` wildcard
// so `/diagnostic/history` isn't captured as a slug.
Route::middleware(['auth'])->group(function (): void {
    Route::get('diagnostic/history', [HistoryController::class, 'index'])
        ->name('diagnostic.history');
    Route::get('diagnostic/history/{diagnostic}', [HistoryController::class, 'show'])
        ->name('diagnostic.history.show');
});

Route::get('diagnostic/{questionnaire:slug}', [DiagnosticController::class, 'show'])
    ->name('diagnostic.show');
Route::post('diagnostic/{questionnaire:slug}/submit', [DiagnosticController::class, 'submit'])
    ->name('diagnostic.submit');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
