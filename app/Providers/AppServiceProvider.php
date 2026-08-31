<?php

namespace App\Providers;

use App\Listeners\AuditAuthEvents;
use App\Models\AnswerOption;
use App\Models\Category;
use App\Models\Content;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\ResultInterpretation;
use App\Models\User;
use App\Observers\AuditableObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        $this->configureAuditing();
        $this->configureSecureTransport();
    }

    /**
     * Génère un nonce partagé avec Vite pour la CSP (voir EnTetesDeSecurite) et
     * force la génération d'URL en HTTPS sur les environnements déployés — jamais
     * en local ni en test, où l'application est servie en clair.
     */
    protected function configureSecureTransport(): void
    {
        Vite::useCspNonce();

        if (app()->environment(['production', 'staging', 'recette'])) {
            URL::forceScheme('https');
        }
    }

    /**
     * Wire the RGPD audit trail: observe every administered model and
     * subscribe to authentication / 2FA events.
     */
    protected function configureAuditing(): void
    {
        $auditable = [
            User::class,
            Category::class,
            Content::class,
            Menu::class,
            MenuItem::class,
            Questionnaire::class,
            Question::class,
            AnswerOption::class,
            ResultInterpretation::class,
        ];

        foreach ($auditable as $model) {
            $model::observe(AuditableObserver::class);
        }

        Event::subscribe(AuditAuthEvents::class);
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
