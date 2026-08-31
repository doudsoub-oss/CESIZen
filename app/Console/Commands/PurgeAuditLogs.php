<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

/**
 * Purge du journal d'audit (L09, Tableau 12) : conservation glissante de 12 mois.
 */
class PurgeAuditLogs extends Command
{
    protected $signature = 'audit:purger';

    protected $description = 'Supprime les entrées du journal d\'audit de plus de 12 mois';

    public function handle(): int
    {
        $threshold = now()->subMonths(12);

        $deleted = AuditLog::query()->where('created_at', '<', $threshold)->delete();

        $this->info("{$deleted} entrée(s) de journal supprimée(s).");

        return self::SUCCESS;
    }
}
