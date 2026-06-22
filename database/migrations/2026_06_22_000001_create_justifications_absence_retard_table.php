<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table des demandes de justification envoyées par les parents.
     *
     * La table absences_retards reste la source officielle.
     * Cette table stocke seulement la demande du parent et la décision de l'école.
     */
    public function up(): void
    {
        Schema::create('justifications_absence_retard', function (Blueprint $table) {
            $table->id();

            $table->foreignId('absence_retard_id')
                ->constrained('absences_retards')
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('motif', 120);
            $table->text('message')->nullable();
            $table->string('piece_jointe')->nullable();

            $table->string('statut', 30)->default('en_attente');

            $table->foreignId('traite_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('traite_le')->nullable();
            $table->text('commentaire_traitement')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique('absence_retard_id');
            $table->index(['parent_id', 'statut']);
            $table->index(['statut', 'created_at']);
        });
    }

    /**
     * Supprime la table si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('justifications_absence_retard');
    }
};
