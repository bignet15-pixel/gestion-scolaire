<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table matieres.
     */
    public function up(): void
    {
        Schema::create('matieres', function (Blueprint $table) {
            $table->id();

            $table->string('nom')->unique();

            $table->unsignedTinyInteger('coefficient_default')->default(1);

            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Supprime la table matieres si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('matieres');
    }
};