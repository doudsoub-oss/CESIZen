<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Vérifie qu'un échantillon de résultats de diagnostic se déchiffre avec la clé
 * applicative COURANTE. Utilisée à la fin d'un test de restauration (L19) : un
 * déchiffrement réussi valide à la fois l'intégrité de la sauvegarde restaurée
 * ET la cohérence de l'APP_KEY avec les données.
 *
 * Sort en échec dès qu'une valeur chiffrée résiste au déchiffrement.
 */
class VerifierDechiffrementDiagnostic extends Command
{
    protected $signature = 'diagnostics:verifier-dechiffrement {--echantillon=100 : Nombre maximal de lignes à contrôler par table}';

    protected $description = 'Vérifie le déchiffrement des résultats de diagnostic avec la clé applicative courante';

    /**
     * @var array<string, list<string>>
     */
    private const COLONNES = [
        'diagnostics' => ['score_total'],
        'diagnostic_responses' => ['score', 'answer_option_id'],
    ];

    public function handle(): int
    {
        $echantillon = max(1, (int) $this->option('echantillon'));
        $verifiees = 0;

        foreach (self::COLONNES as $table => $columns) {
            foreach ($columns as $column) {
                $rows = DB::table($table)
                    ->whereNotNull($column)
                    ->orderBy('id')
                    ->limit($echantillon)
                    ->pluck($column, 'id');

                foreach ($rows as $id => $value) {
                    try {
                        Crypt::decryptString((string) $value);
                    } catch (DecryptException) {
                        $this->error("Déchiffrement impossible : {$table}#{$id}.{$column} — clé applicative incohérente avec les données.");

                        return self::FAILURE;
                    }

                    $verifiees++;
                }
            }
        }

        if ($verifiees === 0) {
            $this->warn('Aucune donnée chiffrée à vérifier (base sans diagnostic).');

            return self::SUCCESS;
        }

        $this->info("Déchiffrement vérifié sur {$verifiees} valeur(s) : clé applicative cohérente.");

        return self::SUCCESS;
    }
}
