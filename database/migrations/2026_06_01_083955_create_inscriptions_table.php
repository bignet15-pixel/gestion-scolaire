<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table inscriptions.
     */
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('eleve_id')
                ->constrained('eleves')
                ->cascadeOnDelete();

            $table->foreignId('classe_id')
                ->constrained('classes')
                ->cascadeOnDelete();

            $table->foreignId('annee_scolaire_id')
                ->constrained('annee_scolaires')
                ->cascadeOnDelete();

            $table->date('date_inscription');

            $table->decimal('frais_attendu', 10, 2)->default(0);

            $table->enum('statut', ['actif', 'termine', 'abandonne', 'transfere'])
                ->default('actif');

            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['eleve_id', 'annee_scolaire_id']);
        });
    }

    /**
     * Supprime la table inscriptions si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};