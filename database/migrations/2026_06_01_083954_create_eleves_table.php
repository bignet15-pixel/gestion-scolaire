<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table eleves.
     */
    public function up(): void
    {
        Schema::create('eleves', function (Blueprint $table) {
            $table->id();

            $table->string('matricule')->unique();

            $table->string('nom');
            $table->string('prenom');

            $table->enum('sexe', ['M', 'F']);

            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();

            $table->string('contact_parent')->nullable();

            $table->string('photo')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Supprime la table eleves si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('eleves');
    }
};