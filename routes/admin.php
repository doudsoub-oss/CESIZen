<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuItemController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| All admin routes live under `/admin` and are gated by `auth`, `verified`,
| `active` and `role:admin`. Concrete resources are added per feature step
| (information module here; diagnostic + account management in later steps).
|
*/

Route::middleware(['auth', 'verified', 'active', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::inertia('/', 'admin/Dashboard')->name('dashboard');

        Route::resource('categories', CategoryController::class)->except('show');
        Route::patch('categories/{category}/toggle-active', [CategoryController::class, 'toggleActive'])
            ->name('categories.toggle-active');

        Route::resource('contents', ContentController::class)->except('show');

        Route::resource('menus', MenuController::class)->except('show');

        Route::scopeBindings()->group(function (): void {
            Route::resource('menus.items', MenuItemController::class)
                ->parameters(['items' => 'item'])
                ->only(['create', 'store', 'edit', 'update', 'destroy']);
        });
    });
