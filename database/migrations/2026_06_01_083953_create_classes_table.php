<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table classes.
     */
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('annee_scolaire_id')
                ->constrained('annee_scolaires')
                ->cascadeOnDelete();

            $table->foreignId('enseignant_principal_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->unsignedBigInteger('chef_classe_id')->nullable();

            $table->enum('niveau', ['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2']);

            $table->string('nom');

            $table->decimal('frais_scolarite', 10, 2)->default(0);

            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Supprime la table classes si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};