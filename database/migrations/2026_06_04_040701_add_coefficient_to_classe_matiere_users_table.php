<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute un coefficient propre à chaque affectation classe / matière.
     */
    public function up(): void
    {
        Schema::table('classe_matiere_users', function (Blueprint $table) {
            if (! Schema::hasColumn('classe_matiere_users', 'coefficient')) {
                $table->decimal('coefficient', 5, 2)
                    ->default(1)
                    ->after('matiere_id');
            }
        });
    }

    /**
     * Retire le coefficient si on annule la migration.
     */
    public function down(): void
    {
        Schema::table('classe_matiere_users', function (Blueprint $table) {
            if (Schema::hasColumn('classe_matiere_users', 'coefficient')) {
                $table->dropColumn('coefficient');
            }
        });
    }
};