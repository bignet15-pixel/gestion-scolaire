<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table paiements.
     */
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inscription_id')
                ->constrained('inscriptions')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('numero_paiement')->unique();

            $table->decimal('montant', 10, 2);

            $table->date('date_paiement');

            $table->enum('mode_paiement', ['especes', 'mobile_money', 'virement', 'autre'])
                ->default('especes');

            $table->string('contact_parent')->nullable();

            $table->string('contact_gestionnaire')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Supprime la table paiements si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};