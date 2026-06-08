<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(function (): Password {
            // Enforced in every environment: at least one lower- and upper-case
            // letter, one digit and one special character (see TODO security note).
            $password = Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();

            // Production hardens the baseline further (length + breach check).
            return app()->isProduction()
                ? $password->min(12)->uncompromised()
                : $password;
        });
    }
}
