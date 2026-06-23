<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table des annonces officielles de l'école.
     */
    public function up(): void
    {
        Schema::create('annonces', function (Blueprint $table) {
            $table->id();

            $table->string('titre');
            $table->text('contenu');
            $table->string('type')->default('information');
            $table->string('priorite')->default('normale');
            $table->string('cible')->default('parents');

            $table->foreignId('classe_id')
                ->nullable()
                ->constrained('classes')
                ->nullOnDelete();

            $table->foreignId('publie_par')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->boolean('est_publiee')->default(false);
            $table->dateTime('date_publication')->nullable();
            $table->dateTime('date_expiration')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['est_publiee', 'date_publication']);
            $table->index(['cible', 'classe_id']);
        });
    }

    /**
     * Supprime la table des annonces.
     */
    public function down(): void
    {
        Schema::dropIfExists('annonces');
    }
};
