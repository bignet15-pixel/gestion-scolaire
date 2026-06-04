<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table emploi_du_temps.
     */
    public function up(): void
    {
        Schema::create('emploi_du_temps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('classe_matiere_user_id')
                ->constrained('classe_matiere_users')
                ->cascadeOnDelete();

            $table->enum('jour', [
                'lundi',
                'mardi',
                'mercredi',
                'jeudi',
                'vendredi',
                'samedi',
            ]);

            $table->time('heure_debut');
            $table->time('heure_fin');

            $table->string('salle')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(
                ['classe_matiere_user_id', 'jour', 'heure_debut', 'heure_fin'],
                'emploi_du_temps_unique'
            );
        });
    }

    /**
     * Supprime la table emploi_du_temps si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('emploi_du_temps');
    }
};