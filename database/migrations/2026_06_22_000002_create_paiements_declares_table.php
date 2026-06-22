<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table des paiements déclarés par les parents.
     *
     * La table paiements reste la source officielle des paiements validés.
     * Une déclaration validée peut ensuite créer une ligne dans paiements.
     */
    public function up(): void
    {
        Schema::create('paiements_declares', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inscription_id')
                ->constrained('inscriptions')
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('montant', 10, 2);
            $table->string('mode_paiement', 30)->default('mobile_money');
            $table->string('reference_transaction')->nullable();
            $table->string('preuve_paiement')->nullable();

            $table->string('statut', 30)->default('en_attente');

            $table->foreignId('valide_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('valide_le')->nullable();

            $table->foreignId('paiement_id')
                ->nullable()
                ->constrained('paiements')
                ->nullOnDelete();

            $table->text('commentaire_validation')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique('paiement_id');
            $table->index(['inscription_id', 'statut']);
            $table->index(['parent_id', 'statut']);
            $table->index(['statut', 'created_at']);
        });
    }

    /**
     * Supprime la table si on annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements_declares');
    }
};
