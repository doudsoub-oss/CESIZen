<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Central writer for the RGPD audit trail.
 *
 * Every sensitive action funnels through here so actor / IP / user-agent
 * capture and the redaction of secret fields happen in exactly one place.
 */
class AuditLogger
{
    /**
     * Attribute names whose values must never be persisted in clear text.
     *
     * @var list<string>
     */
    public const REDACTED_KEYS = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Record a model-level action (create / update / delete).
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        string $action,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $model ? $model::class : null,
            'auditable_id' => $model?->getKey(),
            'old_values' => $this->redact($oldValues),
            'new_values' => $this->redact($newValues),
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
        ]);
    }

    /**
     * Record an authentication / account-lifecycle event.
     *
     * The acting user is taken from the event (it is not always the
     * session user — e.g. a failed login has no authenticated user).
     *
     * @param  array<string, mixed>|null  $context
     */
    public function auth(
        string $action,
        ?Authenticatable $user = null,
        ?array $context = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user?->getAuthIdentifier() ?? auth()->id(),
            'action' => $action,
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => $this->redact($context),
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
        ]);
    }

    /**
     * Replace any sensitive value with a placeholder so the audit row keeps
     * the fact that the field changed without leaking the secret itself.
     *
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    public function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach (self::REDACTED_KEYS as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '[redacted]';
            }
        }

        return $values;
    }

    private function ip(): ?string
    {
        return request()->ip();
    }

    private function userAgent(): ?string
    {
        return request()->userAgent();
    }
}
