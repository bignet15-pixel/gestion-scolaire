<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table trimestres.
     */
    public function up(): void
    {
        Schema::create('trimestres', function (Blueprint $table) {
            $table->id();

            $table->foreignId('annee_scolaire_id')
                ->constrained('annee_scolaires')
                ->cascadeOnDelete();

            $table->string('nom');

            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();

            $table->enum('statut', ['actif', 'ferme'])->default('actif');

            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Supprime la table trimestres si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('trimestres');
    }
};