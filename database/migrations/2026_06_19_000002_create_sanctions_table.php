<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanctions', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('categorie', 30);
            $table->string('mode_declenchement', 30);
            $table->string('statut_declencheur', 30)->default('tous');
            $table->unsignedInteger('seuil')->nullable();
            $table->string('periode_calcul', 30)->nullable();
            $table->string('niveau_gravite', 20)->default('faible');
            $table->string('type_effet', 50);
            $table->decimal('valeur_effet', 10, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('visible_parent_defaut')->default(false);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['active', 'categorie', 'mode_declenchement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanctions');
    }
};
