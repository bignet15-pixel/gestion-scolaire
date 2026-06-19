<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convertit les anciennes dates de créneau en semaines lundi-samedi.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE emploi_du_temps
            SET date_fin = DATE_ADD(DATE_SUB(date_debut, INTERVAL WEEKDAY(date_debut) DAY), INTERVAL 5 DAY),
                date_debut = DATE_SUB(date_debut, INTERVAL WEEKDAY(date_debut) DAY)
            WHERE date_debut IS NOT NULL
        ");
    }

    /**
     * Cette normalisation ne peut pas être annulée sans connaître les anciennes dates.
     */
    public function down(): void
    {
        //
    }
};
