<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanctions_appliquees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')
                ->constrained('inscriptions')
                ->cascadeOnDelete();
            $table->foreignId('sanction_id')
                ->constrained('sanctions')
                ->restrictOnDelete();
            $table->foreignId('trimestre_id')
                ->nullable()
                ->constrained('trimestres')
                ->nullOnDelete();
            $table->string('origine', 30);
            $table->date('date_application')->nullable();
            $table->date('periode_debut')->nullable();
            $table->date('periode_fin')->nullable();
            $table->unsignedInteger('nombre_evenements')->default(0);
            $table->text('motif')->nullable();
            $table->text('commentaire_interne')->nullable();
            $table->string('statut', 30)->default('proposee');
            $table->boolean('visible_parent')->default(false);
            $table->string('type_effet', 50);
            $table->decimal('valeur_effet', 10, 2)->nullable();
            $table->foreignId('applique_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('decision_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('decision_le')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['inscription_id', 'statut']);
            $table->index(['sanction_id', 'periode_debut', 'periode_fin']);
            $table->index(['trimestre_id', 'type_effet', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanctions_appliquees');
    }
};
