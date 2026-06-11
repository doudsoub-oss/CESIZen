<?php

use App\Http\Controllers\Admin\AnswerOptionController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuestionnaireController;
use App\Http\Controllers\Admin\ResultInterpretationController;
use App\Http\Controllers\Admin\UserController;
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

        /*
        |----------------------------------------------------------------
        | Diagnostic module admin (§5.3 + §5.4)
        |----------------------------------------------------------------
        */
        Route::resource('questionnaires', QuestionnaireController::class)->except('show');

        Route::scopeBindings()->group(function (): void {
            Route::resource('questionnaires.questions', QuestionController::class)
                ->parameters(['questions' => 'question'])
                ->only(['create', 'store', 'edit', 'update', 'destroy']);

            Route::resource('questionnaires.questions.answer-options', AnswerOptionController::class)
                ->parameters(['questions' => 'question', 'answer-options' => 'answerOption'])
                ->only(['create', 'store', 'edit', 'update', 'destroy']);

            Route::resource('questionnaires.interpretations', ResultInterpretationController::class)
                ->parameters(['interpretations' => 'interpretation'])
                ->only(['create', 'store', 'edit', 'update', 'destroy']);
        });

        /*
        |----------------------------------------------------------------
        | Account management (§5.1 + §5.4)
        |----------------------------------------------------------------
        */
        Route::resource('users', UserController::class)
            ->only(['index', 'create', 'store', 'show', 'destroy']);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
            ->name('users.toggle-active');
        Route::patch('users/{user}/role', [UserController::class, 'changeRole'])
            ->name('users.change-role');

        /*
        |----------------------------------------------------------------
        | RGPD audit trail (§6.4 — read-only viewer)
        |----------------------------------------------------------------
        */
        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');
    });
