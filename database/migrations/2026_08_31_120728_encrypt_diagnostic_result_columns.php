<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chiffrement applicatif au repos des résultats de diagnostic (L05, Tableau 10).
 *
 * Les colonnes passent en `text` : le chiffré est plus long que le clair et
 * n'est pas numérique. La clé étrangère `answer_option_id` est retirée — une
 * valeur chiffrée ne peut plus référencer `answer_options.id` — ce qui rend le
 * score non reconstructible par jointure SQL (scénario R1). La lecture des
 * libellés de réponse se fait ensuite via Eloquent (clés collectées en PHP).
 *
 * Migration réversible. Elle change un type en place : légitime car aucun
 * environnement n'est déployé à sa date d'application (voir ADR 0002, L05c).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostics', function (Blueprint $table) {
            $table->text('score_total')->nullable()->change();
        });

        Schema::table('diagnostic_responses', function (Blueprint $table) {
            $table->dropForeign(['answer_option_id']);
        });

        Schema::table('diagnostic_responses', function (Blueprint $table) {
            $table->text('answer_option_id')->change();
            $table->text('score')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Réversion structurelle. À n'exécuter que sur des données en clair
        // (déchiffrer au préalable, cf. rotation-des-secrets.md).
        Schema::table('diagnostic_responses', function (Blueprint $table) {
            $table->unsignedBigInteger('answer_option_id')->change();
            $table->integer('score')->default(0)->change();
        });

        Schema::table('diagnostic_responses', function (Blueprint $table) {
            $table->foreign('answer_option_id')->references('id')->on('answer_options')->cascadeOnDelete();
        });

        Schema::table('diagnostics', function (Blueprint $table) {
            $table->integer('score_total')->default(0)->change();
        });
    }
};
