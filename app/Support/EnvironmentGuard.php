<?php

namespace App\Support;

use RuntimeException;

/**
 * Garde-fous de cloisonnement des environnements (traite R10).
 *
 * La logique est isolée ici, indépendante du cycle de démarrage, afin d'être
 * vérifiable directement par des tests sans reconstruire l'application.
 */
class EnvironmentGuard
{
    /**
     * Environnements déployés où le débogage doit toujours être désactivé.
     *
     * @var list<string>
     */
    private const ENVIRONNEMENTS_PROTEGES = ['production', 'staging', 'recette'];

    /**
     * Empêche un démarrage avec le débogage actif hors développement : un
     * débogage actif hors développement doit empêcher l'application de démarrer,
     * pas produire un simple avertissement.
     *
     * @throws RuntimeException
     */
    public static function ensureDebugIsDisabled(string $environment, bool $debug): void
    {
        if (in_array($environment, self::ENVIRONNEMENTS_PROTEGES, true) && $debug) {
            throw new RuntimeException(
                "Le débogage (APP_DEBUG) doit être désactivé en environnement « {$environment} ». ".
                'Démarrage interrompu.'
            );
        }
    }
}
