<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table des demandes de réinscription faites par les parents.
     *
     * La table inscriptions reste la source officielle.
     * Cette table représente uniquement la demande et sa validation.
     */
    public function up(): void
    {
        Schema::create('demandes_reinscription', function (Blueprint $table) {
            $table->id();

            $table->foreignId('eleve_id')
                ->constrained('eleves')
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('ancienne_inscription_id')
                ->nullable()
                ->constrained('inscriptions')
                ->nullOnDelete();

            $table->foreignId('ancienne_classe_id')
                ->nullable()
                ->constrained('classes')
                ->nullOnDelete();

            $table->foreignId('nouvelle_annee_scolaire_id')
                ->constrained('annee_scolaires')
                ->restrictOnDelete();

            $table->foreignId('classe_demandee_id')
                ->constrained('classes')
                ->restrictOnDelete();

            $table->string('type_demande', 40);
            $table->string('decision_systeme', 40);
            $table->string('statut', 30)->default('en_attente');

            $table->foreignId('inscription_creee_id')
                ->nullable()
                ->constrained('inscriptions')
                ->nullOnDelete();

            $table->text('commentaire_parent')->nullable();

            $table->foreignId('valide_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('valide_le')->nullable();
            $table->text('commentaire_gestionnaire')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique('inscription_creee_id');
            $table->index(['eleve_id', 'nouvelle_annee_scolaire_id', 'statut'], 'demandes_reinscription_eleve_annee_statut_idx');
            $table->index(['parent_id', 'statut']);
            $table->index(['statut', 'created_at']);
        });
    }

    /**
     * Supprime la table si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_reinscription');
    }
};
