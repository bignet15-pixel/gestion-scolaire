<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements_declares', function (Blueprint $table) {
            $table->string('numero_transfert', 50)
                ->nullable()
                ->after('mode_paiement');
        });
    }

    public function down(): void
    {
        Schema::table('paiements_declares', function (Blueprint $table) {
            $table->dropColumn('numero_transfert');
        });
    }
};
