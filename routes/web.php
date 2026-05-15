<?php

use App\Http\Controllers\Information\CategoryController;
use App\Http\Controllers\Information\ContentController;
use App\Http\Controllers\Information\InformationController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

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

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
