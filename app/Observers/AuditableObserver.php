<?php

namespace App\Observers;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Generic observer attached to every administered model. It turns Eloquent
 * lifecycle events into audit-log rows (`{model}.{created|updated|deleted}`)
 * carrying the redacted attribute diff.
 */
class AuditableObserver
{
    /**
     * Columns whose change is already reported by the dedicated auth-event
     * listeners (login, password update, 2FA). An update touching *only*
     * these is skipped here so the trail isn't duplicated.
     *
     * @var list<string>
     */
    private const AUTH_MANAGED_COLUMNS = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'remember_token',
    ];

    public function __construct(private readonly AuditLogger $logger) {}

    public function created(Model $model): void
    {
        $this->logger->log(
            $this->action($model, 'created'),
            $model,
            null,
            $this->withoutTimestamps($model->getAttributes()),
        );
    }

    public function updated(Model $model): void
    {
        $changes = $this->withoutTimestamps($model->getChanges());

        if ($changes === []) {
            return;
        }

        // Auth-managed columns are audited by the auth-event listeners.
        if (array_diff(array_keys($changes), self::AUTH_MANAGED_COLUMNS) === []) {
            return;
        }

        $original = array_intersect_key($model->getRawOriginal(), $changes);

        $this->logger->log(
            $this->action($model, 'updated'),
            $model,
            $original,
            $changes,
        );
    }

    public function deleted(Model $model): void
    {
        $this->logger->log(
            $this->action($model, 'deleted'),
            $model,
            $this->withoutTimestamps($model->getAttributes()),
            null,
        );
    }

    /**
     * Build the action key, e.g. `App\Models\Content` => `content.updated`.
     */
    private function action(Model $model, string $event): string
    {
        return Str::snake(class_basename($model)).'.'.$event;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withoutTimestamps(array $attributes): array
    {
        unset($attributes['created_at'], $attributes['updated_at']);

        return $attributes;
    }
}
