<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée les notifications internes visibles dans les espaces utilisateurs.
     */
    public function up(): void
    {
        Schema::create('notifications_utilisateurs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('titre');
            $table->text('message');
            $table->string('type')->default('information');
            $table->string('lien')->nullable();

            $table->nullableMorphs('source');

            $table->boolean('lue')->default(false);
            $table->dateTime('lue_le')->nullable();

            $table->string('email_mode')->default('alerte');
            $table->text('email_resume')->nullable();
            $table->text('email_raison_connexion')->nullable();
            $table->string('email_statut')->default('pending');
            $table->dateTime('email_envoye_le')->nullable();
            $table->text('email_erreur')->nullable();

            $table->json('metadata')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'lue']);
            $table->index(['user_id', 'type']);
            $table->index(['email_statut']);
        });
    }

    /**
     * Supprime les notifications internes.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications_utilisateurs');
    }
};
