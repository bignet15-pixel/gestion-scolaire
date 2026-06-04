<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table annee_scolaires.
     */
    public function up(): void
    {
        Schema::create('annee_scolaires', function (Blueprint $table) {
            $table->id();

            $table->string('libelle')->unique();
            $table->date('date_debut');
            $table->date('date_fin');

            $table->enum('statut', ['active', 'fermee'])->default('active');

            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Supprime la table annee_scolaires si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('annee_scolaires');
    }
};