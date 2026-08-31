<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;

/**
 * Subscriber translating authentication / 2FA events into audit-log rows.
 * Account-data changes (name, email, role, is_active) are handled by the
 * AuditableObserver instead.
 */
class AuditAuthEvents
{
    public function __construct(private readonly AuditLogger $logger) {}

    public function handleLogin(Login $event): void
    {
        $this->logger->auth('auth.login', $event->user);

        // Alimente la dernière connexion (base des durées de conservation, L09).
        // saveQuietly : pas d'entrée « user.updated » à chaque connexion.
        if ($event->user instanceof User) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        }
    }

    public function handleLogout(Logout $event): void
    {
        $this->logger->auth('auth.logout', $event->user);
    }

    public function handleFailed(Failed $event): void
    {
        // No authenticated user on a failed attempt; keep the tried email
        // so repeated failures against an account are traceable.
        $this->logger->auth('auth.failed', $event->user, [
            'email' => $event->credentials['email'] ?? null,
        ]);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->logger->auth('auth.password_reset', $event->user);
    }

    public function handlePasswordUpdated(PasswordUpdatedViaController $event): void
    {
        $this->logger->auth('auth.password_updated', $event->user);
    }

    public function handleTwoFactorEnabled(TwoFactorAuthenticationEnabled $event): void
    {
        $this->logger->auth('auth.two_factor_enabled', $event->user);
    }

    public function handleTwoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        $this->logger->auth('auth.two_factor_confirmed', $event->user);
    }

    public function handleTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $this->logger->auth('auth.two_factor_disabled', $event->user);
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            PasswordReset::class => 'handlePasswordReset',
            PasswordUpdatedViaController::class => 'handlePasswordUpdated',
            TwoFactorAuthenticationEnabled::class => 'handleTwoFactorEnabled',
            TwoFactorAuthenticationConfirmed::class => 'handleTwoFactorConfirmed',
            TwoFactorAuthenticationDisabled::class => 'handleTwoFactorDisabled',
        ];
    }
}
