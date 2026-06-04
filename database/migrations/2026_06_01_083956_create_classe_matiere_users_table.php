<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table classe_matiere_users.
     * Cette table permet d'affecter un enseignant à une matière dans une classe.
     */
    public function up(): void
    {
        Schema::create('classe_matiere_users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('classe_id')
                ->constrained('classes')
                ->cascadeOnDelete();

            $table->foreignId('matiere_id')
                ->constrained('matieres')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('date_debut');
            $table->date('date_fin')->nullable();

            $table->enum('statut', ['actif', 'termine', 'suspendu'])
                ->default('actif');

            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(
                ['classe_id', 'matiere_id', 'user_id', 'date_debut'],
                'affectation_classe_matiere_user_unique'
            );
        });
    }

    /**
     * Supprime la table classe_matiere_users si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('classe_matiere_users');
    }
};