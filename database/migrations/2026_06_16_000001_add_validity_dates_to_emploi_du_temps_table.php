<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute une période de validité aux créneaux d'emploi du temps.
     */
    public function up(): void
    {
        Schema::table('emploi_du_temps', function (Blueprint $table) {
            $table->index('classe_matiere_user_id', 'emploi_du_temps_affectation_index');
        });

        Schema::table('emploi_du_temps', function (Blueprint $table) {
            $table->dropUnique('emploi_du_temps_unique');
        });

        Schema::table('emploi_du_temps', function (Blueprint $table) {
            $table->date('date_debut')->nullable()->after('salle');
            $table->date('date_fin')->nullable()->after('date_debut');
        });

        DB::statement("
            UPDATE emploi_du_temps
            INNER JOIN classe_matiere_users
                ON classe_matiere_users.id = emploi_du_temps.classe_matiere_user_id
            SET emploi_du_temps.date_debut = classe_matiere_users.date_debut,
                emploi_du_temps.date_fin = classe_matiere_users.date_fin
            WHERE emploi_du_temps.date_debut IS NULL
        ");

        Schema::table('emploi_du_temps', function (Blueprint $table) {
            $table->unique(
                ['classe_matiere_user_id', 'jour', 'heure_debut', 'heure_fin', 'date_debut'],
                'emploi_du_temps_unique'
            );
        });
    }

    /**
     * Retire la période de validité des créneaux.
     */
    public function down(): void
    {
        Schema::table('emploi_du_temps', function (Blueprint $table) {
            $table->dropUnique('emploi_du_temps_unique');
        });

        Schema::table('emploi_du_temps', function (Blueprint $table) {
            $table->dropColumn(['date_debut', 'date_fin']);
        });

        Schema::table('emploi_du_temps', function (Blueprint $table) {
            $table->unique(
                ['classe_matiere_user_id', 'jour', 'heure_debut', 'heure_fin'],
                'emploi_du_temps_unique'
            );
        });

        Schema::table('emploi_du_temps', function (Blueprint $table) {
            $table->dropIndex('emploi_du_temps_affectation_index');
        });
    }
};
