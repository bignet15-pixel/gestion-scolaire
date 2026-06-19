<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absences_retards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')
                ->constrained('inscriptions')
                ->cascadeOnDelete();
            $table->string('type', 20);
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('periode', 30)->default('journee');
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();
            $table->time('heure_arrivee')->nullable();
            $table->unsignedInteger('duree_minutes')->nullable();
            $table->string('categorie_motif', 30)->default('non_renseigne');
            $table->text('motif')->nullable();
            $table->string('statut', 30)->default('en_attente');
            $table->text('justification')->nullable();
            $table->string('piece_justificative')->nullable();
            $table->text('commentaire_interne')->nullable();
            $table->string('source_signalement', 30);
            $table->boolean('visible_parent')->default(true);
            $table->foreignId('enregistre_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('statut_mis_a_jour_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('statut_mis_a_jour_le')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['inscription_id', 'type', 'date_debut']);
            $table->index(['statut', 'date_debut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences_retards');
    }
};
