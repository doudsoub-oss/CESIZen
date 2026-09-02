<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Purge des comptes inactifs (L09) : supprime les comptes usagers sans connexion
 * depuis 24 mois AYANT reçu leur préavis. La suppression emprunte le même chemin
 * que la suppression volontaire — `$user->delete()` — donc les cascades effacent
 * les diagnostics et l'observateur journalise « user.deleted ».
 */
class PurgeInactiveAccounts extends Command
{
    protected $signature = 'comptes:purger-inactifs {--dry-run : N\'affiche que le nombre concerné} {--limit= : Nombre maximal de comptes purgés}';

    protected $description = 'Purge les comptes inactifs depuis 24 mois ayant reçu leur préavis';

    public function handle(): int
    {
        $threshold = now()->subMonths(24);

        $query = User::query()
            ->where('role', Role::User)
            ->whereNotNull('inactivity_notified_at')
            ->inactiveSince($threshold);

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $users = $query->get();

        if ($this->option('dry-run')) {
            $this->info("{$users->count()} compte(s) seraient purgé(s).");

            return self::SUCCESS;
        }

        foreach ($users as $user) {
            $user->delete();
        }

        $this->info("{$users->count()} compte(s) inactif(s) purgé(s).");

        return self::SUCCESS;
    }
}
