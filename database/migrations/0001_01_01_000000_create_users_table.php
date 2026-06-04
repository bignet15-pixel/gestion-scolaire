<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée les tables users, password_reset_tokens et sessions.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Champ utilisé par Laravel Breeze pour l'authentification.
            $table->string('name');

            // Informations personnelles de l'utilisateur.
            $table->string('nom')->nullable();
            $table->string('prenom')->nullable();
            $table->enum('sexe', ['M', 'F'])->nullable();

            // Informations de connexion.
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->nullable()->unique();
            $table->string('password');

            // Rôle de l'utilisateur dans le système.
            $table->enum('role', ['gestionnaire', 'enseignant'])->default('enseignant');

            // Informations complémentaires.
            $table->string('adresse')->nullable();
            $table->string('matricule')->nullable()->unique();

            // Remember token utilisé par Laravel pour "se souvenir de moi".
            $table->rememberToken();

            // Suppression logique personnalisée.
            $table->boolean('is_deleted')->default(false);

            // Champ Laravel pour la suppression douce : deleted_at.
            $table->softDeletes();

            // Champs Laravel : created_at et updated_at.
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Supprime les tables si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};