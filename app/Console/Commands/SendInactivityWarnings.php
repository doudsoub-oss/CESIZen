<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\AccountInactivityWarning;
use Illuminate\Console\Command;

/**
 * Préavis d'inactivité (L09) : repère les comptes usagers sans connexion depuis
 * 23 mois, leur envoie un préavis et marque la date d'envoi pour ne pas le
 * renvoyer chaque jour.
 */
class SendInactivityWarnings extends Command
{
    protected $signature = 'comptes:preavis-inactivite';

    protected $description = 'Envoie un préavis aux comptes inactifs depuis 23 mois';

    public function handle(): int
    {
        $threshold = now()->subMonths(23);

        $users = User::query()
            ->where('role', Role::User)
            ->whereNull('inactivity_notified_at')
            ->inactiveSince($threshold)
            ->get();

        foreach ($users as $user) {
            $user->notify(new AccountInactivityWarning);
            $user->forceFill(['inactivity_notified_at' => now()])->saveQuietly();
        }

        $this->info("{$users->count()} préavis d'inactivité envoyé(s).");

        return self::SUCCESS;
    }
}
