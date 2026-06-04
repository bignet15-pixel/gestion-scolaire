<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table notes.
     */
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inscription_id')
                ->constrained('inscriptions')
                ->cascadeOnDelete();

            $table->foreignId('evaluation_id')
                ->constrained('evaluations')
                ->cascadeOnDelete();

            $table->decimal('valeur', 5, 2);

            $table->string('appreciation')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['inscription_id', 'evaluation_id'], 'note_inscription_evaluation_unique');
        });
    }

    /**
     * Supprime la table notes si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};