<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| All admin routes live under `/admin` and are gated by `auth`, `verified`,
| `active` and `role:admin`. Concrete CRUD resources are added in subsequent
| feature branches (information module, diagnostic module, account
| management, audit log viewer).
|
*/

Route::middleware(['auth', 'verified', 'active', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::inertia('/', 'admin/Dashboard')->name('dashboard');
    });
