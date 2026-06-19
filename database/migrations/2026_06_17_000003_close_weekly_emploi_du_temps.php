<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Referme les créneaux sur leur semaine exacte lundi-samedi.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE emploi_du_temps
            SET date_fin = DATE_ADD(date_debut, INTERVAL 5 DAY)
            WHERE date_debut IS NOT NULL
                AND date_fin IS NULL
        ");
    }

    /**
     * Cette correction suit la logique semaine exacte et ne doit pas être annulée automatiquement.
     */
    public function down(): void
    {
        //
    }
};
