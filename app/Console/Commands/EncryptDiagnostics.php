<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Chiffre au repos les colonnes de résultats de diagnostic laissées EN CLAIR
 * (données antérieures à la mise en place du chiffrement, L05). Idempotente : la
 * relancer ne rechiffre pas ce qui l'est déjà.
 *
 * Lit via le Query Builder (donc SANS les casts du modèle) pour manipuler les
 * valeurs brutes.
 */
class EncryptDiagnostics extends Command
{
    protected $signature = 'diagnostics:chiffrer {--dry-run : Affiche le nombre de lignes concernées sans rien modifier}';

    protected $description = 'Chiffre les résultats de diagnostic déjà en clair (idempotent)';

    /**
     * Colonnes chiffrées par table.
     *
     * @var array<string, list<string>>
     */
    private const COLONNES = [
        'diagnostics' => ['score_total'],
        'diagnostic_responses' => ['score', 'answer_option_id'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        foreach (self::COLONNES as $table => $columns) {
            $count = $this->encryptTable($table, $columns, $dryRun);
            $total += $count;
            $this->line("  {$table} : {$count} ligne(s) ".($dryRun ? 'à chiffrer' : 'chiffrée(s)'));
        }

        $this->info($dryRun
            ? "{$total} ligne(s) en clair détectée(s) (aucune modification)."
            : "Terminé : {$total} ligne(s) chiffrée(s).");

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $columns
     */
    private function encryptTable(string $table, array $columns, bool $dryRun): int
    {
        $affected = 0;

        DB::table($table)->orderBy('id')->chunkById(500, function ($rows) use ($table, $columns, $dryRun, &$affected): void {
            DB::transaction(function () use ($rows, $table, $columns, $dryRun, &$affected): void {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $column) {
                        $value = $row->{$column};

                        if ($value === null || $this->estChiffre((string) $value)) {
                            continue;
                        }

                        $updates[$column] = Crypt::encryptString((string) $value);
                    }

                    if ($updates === []) {
                        continue;
                    }

                    $affected++;

                    if (! $dryRun) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
        });

        return $affected;
    }

    /**
     * Une valeur est déjà chiffrée si elle se déchiffre sans erreur.
     */
    private function estChiffre(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
