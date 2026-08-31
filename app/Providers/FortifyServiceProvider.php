<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class FortifyServiceProvider extends ServiceProvider
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
        $this->configureActions();
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureRouteThrottling();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Replace Fortify's default email+password check so deactivated accounts
     * cannot authenticate. The friendly message surfaces on the login form.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return null;
            }

            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    Fortify::username() => __('Votre compte a été désactivé. Contactez un administrateur.'),
                ]);
            }

            return $user;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister' => Features::enabled(Features::registration()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register'));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting for authentication surfaces (traite R6).
     *
     * Le plafonnement est double sur la connexion : par couple (email + IP) pour
     * contrer le bourrage depuis une adresse, ET par compte seul pour contrer un
     * compte ciblé depuis plusieurs adresses. La réinitialisation est plafonnée
     * par email et par IP, l'inscription par IP.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input(Fortify::username()));
            $emailAndIp = Str::transliterate($email.'|'.$request->ip());

            return [
                Limit::perMinute(5)->by($emailAndIp)->response($this->throttledResponse()),
                Limit::perHour(10)->by('account:'.$email)->response($this->throttledResponse()),
            ];
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perHour(3)->by('pwreset-email:'.$email)->response($this->throttledResponse()),
                Limit::perHour(10)->by('pwreset-ip:'.$request->ip())->response($this->throttledResponse()),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(5)->by('register-ip:'.$request->ip())->response($this->throttledResponse());
        });
    }

    /**
     * Attach the reset / register limiters to the routes Fortify registers
     * itself, without editing any vendor file.
     *
     * Fortify loads its routes from a deferred `booted` callback registered
     * after this provider's, so a single `booted` here would run too early. The
     * nested `booted` is appended while the first round of callbacks is still
     * running, so it fires *after* Fortify's route loading, once the named
     * routes exist.
     */
    private function configureRouteThrottling(): void
    {
        $this->app->booted(function () {
            $this->app->booted(function () {
                $routes = $this->app['router']->getRoutes();

                $routes->getByName('password.email')?->middleware('throttle:password-reset');
                $routes->getByName('register.store')?->middleware('throttle:register');
            });
        });
    }

    /**
     * Generic 429 response, in French, that never reveals whether an account
     * exists. Reused by every authentication limiter above.
     */
    private function throttledResponse(): callable
    {
        return function (Request $request, array $headers): Response {
            return response(
                __('Trop de tentatives. Veuillez patienter avant de réessayer.'),
                Response::HTTP_TOO_MANY_REQUESTS,
                $headers,
            );
        };
    }
}
