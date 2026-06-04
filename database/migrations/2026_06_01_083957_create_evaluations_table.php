<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table evaluations.
     */
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('classe_id')
                ->constrained('classes')
                ->cascadeOnDelete();

            $table->foreignId('matiere_id')
                ->constrained('matieres')
                ->cascadeOnDelete();

            $table->foreignId('trimestre_id')
                ->constrained('trimestres')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('nom');

            $table->enum('type', ['devoir', 'interrogation', 'composition', 'test'])
                ->default('devoir');

            $table->date('date_evaluation')->nullable();

            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();

            $table->decimal('coefficient', 4, 2)->default(1);

            $table->decimal('bareme', 5, 2)->default(20);

            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Supprime la table evaluations si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};