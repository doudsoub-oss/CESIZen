<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Rechiffre les résultats de diagnostic après une rotation d'APP_KEY : déchiffre
 * avec l'ANCIENNE clé, rechiffre avec la clé applicative courante. À exécuter
 * pendant la fenêtre de rotation (voir docs/exploitation/rotation-des-secrets.md).
 *
 * Idempotente : une valeur déjà lisible avec la clé courante est laissée telle
 * quelle.
 */
class ReEncryptDiagnostics extends Command
{
    protected $signature = 'diagnostics:rechiffrer {--ancienne-cle= : Ancienne APP_KEY (format base64:...)}';

    protected $description = 'Rechiffre les résultats de diagnostic après rotation d\'APP_KEY';

    /**
     * @var array<string, list<string>>
     */
    private const COLONNES = [
        'diagnostics' => ['score_total'],
        'diagnostic_responses' => ['score', 'answer_option_id'],
    ];

    public function handle(): int
    {
        $ancienneCle = (string) $this->option('ancienne-cle');

        if ($ancienneCle === '') {
            $this->error('L\'option --ancienne-cle est obligatoire (format base64:...).');

            return self::FAILURE;
        }

        $ancien = $this->encrypteurPour($ancienneCle);
        $total = 0;

        foreach (self::COLONNES as $table => $columns) {
            $total += $this->reEncryptTable($table, $columns, $ancien);
        }

        $this->info("Terminé : {$total} ligne(s) rechiffrée(s) avec la clé courante.");

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $columns
     */
    private function reEncryptTable(string $table, array $columns, Encrypter $ancien): int
    {
        $affected = 0;

        DB::table($table)->orderBy('id')->chunkById(500, function ($rows) use ($table, $columns, $ancien, &$affected): void {
            DB::transaction(function () use ($rows, $table, $columns, $ancien, &$affected): void {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $column) {
                        $value = $row->{$column};

                        if ($value === null || $this->lisibleAvecCleCourante((string) $value)) {
                            continue;
                        }

                        try {
                            $clair = $ancien->decryptString((string) $value);
                        } catch (DecryptException) {
                            // Ni la clé courante ni l'ancienne : on laisse en l'état.
                            $this->warn("Ligne {$table}#{$row->id} : {$column} illisible avec les deux clés, ignorée.");

                            continue;
                        }

                        $updates[$column] = Crypt::encryptString($clair);
                    }

                    if ($updates === []) {
                        continue;
                    }

                    $affected++;
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            });
        });

        return $affected;
    }

    private function lisibleAvecCleCourante(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }

    private function encrypteurPour(string $key): Encrypter
    {
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return new Encrypter($key, config('app.cipher'));
    }
}
