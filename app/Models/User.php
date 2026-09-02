<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'privacy_accepted_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
            'privacy_accepted_at' => 'datetime',
            'last_login_at' => 'datetime',
            'inactivity_notified_at' => 'datetime',
            'role' => Role::class,
        ];
    }

    /**
     * Comptes inactifs depuis (au moins) la date donnée. L'inactivité se mesure
     * sur la dernière connexion, à défaut sur la date de création du compte.
     */
    public function scopeInactiveSince(Builder $query, \DateTimeInterface $date): Builder
    {
        return $query->whereRaw('COALESCE(last_login_at, created_at) <= ?', [$date]);
    }

    public function diagnostics(): HasMany
    {
        return $this->hasMany(Diagnostic::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'created_by');
    }

    public function questionnaires(): HasMany
    {
        return $this->hasMany(Questionnaire::class, 'created_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function hasRole(Role|string $role): bool
    {
        $role = is_string($role) ? Role::from($role) : $role;

        return $this->role === $role;
    }

    public function isAdmin(): bool
    {
        return $this->role?->isAtLeast(Role::Admin) ?? false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }
}
