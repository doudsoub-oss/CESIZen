<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consentement explicite (L08, art. 9.2.a RGPD).
 *
 * - `diagnostics.consented_at` : horodatage du consentement au rattachement du
 *   résultat de santé au compte (sans lui, le résultat n'est pas conservé).
 * - `users.privacy_accepted_at` : horodatage de l'acceptation de la politique de
 *   confidentialité à l'inscription.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostics', function (Blueprint $table) {
            $table->timestamp('consented_at')->nullable()->after('completed_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('privacy_accepted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('diagnostics', function (Blueprint $table) {
            $table->dropColumn('consented_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('privacy_accepted_at');
        });
    }
};
