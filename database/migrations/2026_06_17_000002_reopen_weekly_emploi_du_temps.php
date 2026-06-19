<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rouvre les créneaux transformés à tort en créneaux d'une seule semaine.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE emploi_du_temps
            SET date_fin = NULL
            WHERE date_fin = DATE_ADD(date_debut, INTERVAL 5 DAY)
        ");
    }

    /**
     * La réouverture corrige une normalisation précédente et ne doit pas être annulée automatiquement.
     */
    public function down(): void
    {
        //
    }
};
