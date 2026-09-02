<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi de l'inactivité pour les durées de conservation (L09, Tableau 12).
 *
 * - `last_login_at` : dernière connexion, alimentée à chaque authentification.
 * - `inactivity_notified_at` : date d'envoi du préavis d'inactivité (23 mois),
 *   pour ne pas le renvoyer chaque jour.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('inactivity_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'inactivity_notified_at']);
        });
    }
};
