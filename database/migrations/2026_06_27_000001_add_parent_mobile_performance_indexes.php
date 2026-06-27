<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute des index ciblés pour les écrans parent web/mobile et les volumes de notifications.
     *
     * Les index sont ajoutés de façon défensive pour éviter les doublons si une migration
     * précédente a déjà créé une partie des index sur un poste de développement.
     */
    public function up(): void
    {
        $this->addIndexIfMissing('inscriptions', ['classe_id', 'annee_scolaire_id', 'statut'], 'idx_inscriptions_classe_annee_statut');
        $this->addIndexIfMissing('inscriptions', ['annee_scolaire_id', 'statut'], 'idx_inscriptions_annee_statut');

        $this->addIndexIfMissing('evaluations', ['classe_id', 'trimestre_id', 'matiere_id', 'date_evaluation'], 'idx_evaluations_classe_trim_mat_date');
        $this->addIndexIfMissing('notes', ['evaluation_id', 'inscription_id'], 'idx_notes_evaluation_inscription');

        $this->addIndexIfMissing('paiements', ['inscription_id', 'date_paiement'], 'idx_paiements_inscription_date');
        $this->addIndexIfMissing('paiements', ['user_id', 'date_paiement'], 'idx_paiements_user_date');

        $this->addIndexIfMissing('paiements_declares', ['statut', 'created_at'], 'idx_paiements_declares_statut_date');
        $this->addIndexIfMissing('paiements_declares', ['parent_id', 'created_at'], 'idx_paiements_declares_parent_date');

        $this->addIndexIfMissing('absences_retards', ['inscription_id', 'visible_parent', 'date_debut'], 'idx_absences_visible_inscription_date');
        $this->addIndexIfMissing('absences_retards', ['inscription_id', 'statut', 'date_debut'], 'idx_absences_inscription_statut_date');

        $this->addIndexIfMissing('sanctions_appliquees', ['inscription_id', 'visible_parent', 'trimestre_id', 'statut'], 'idx_sanctions_visible_inscription_tri');
        $this->addIndexIfMissing('sanctions_appliquees', ['inscription_id', 'created_at'], 'idx_sanctions_inscription_date');

        $this->addIndexIfMissing('notifications_utilisateurs', ['user_id', 'lue', 'created_at'], 'idx_notifications_user_lue_created');
        $this->addIndexIfMissing('notifications_utilisateurs', ['user_id', 'type', 'created_at'], 'idx_notifications_user_type_created');
        $this->addIndexIfMissing('notifications_utilisateurs', ['source_type', 'source_id'], 'idx_notifications_source');
        $this->addIndexIfMissing('notifications_utilisateurs', ['email_statut', 'created_at'], 'idx_notifications_email_statut_date');

        $this->addIndexIfMissing('annonces', ['est_publiee', 'type', 'priorite', 'date_publication'], 'idx_annonces_publiee_type_priority');
        $this->addIndexIfMissing('annonces', ['date_expiration', 'est_publiee'], 'idx_annonces_expiration_publiee');

        $this->addIndexIfMissing('demandes_reinscription', ['parent_id', 'created_at'], 'idx_demandes_reinscription_parent_date');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('demandes_reinscription', 'idx_demandes_reinscription_parent_date');

        $this->dropIndexIfExists('annonces', 'idx_annonces_expiration_publiee');
        $this->dropIndexIfExists('annonces', 'idx_annonces_publiee_type_priority');

        $this->dropIndexIfExists('notifications_utilisateurs', 'idx_notifications_email_statut_date');
        $this->dropIndexIfExists('notifications_utilisateurs', 'idx_notifications_source');
        $this->dropIndexIfExists('notifications_utilisateurs', 'idx_notifications_user_type_created');
        $this->dropIndexIfExists('notifications_utilisateurs', 'idx_notifications_user_lue_created');

        $this->dropIndexIfExists('sanctions_appliquees', 'idx_sanctions_inscription_date');
        $this->dropIndexIfExists('sanctions_appliquees', 'idx_sanctions_visible_inscription_tri');

        $this->dropIndexIfExists('absences_retards', 'idx_absences_inscription_statut_date');
        $this->dropIndexIfExists('absences_retards', 'idx_absences_visible_inscription_date');

        $this->dropIndexIfExists('paiements_declares', 'idx_paiements_declares_parent_date');
        $this->dropIndexIfExists('paiements_declares', 'idx_paiements_declares_statut_date');

        $this->dropIndexIfExists('paiements', 'idx_paiements_user_date');
        $this->dropIndexIfExists('paiements', 'idx_paiements_inscription_date');

        $this->dropIndexIfExists('notes', 'idx_notes_evaluation_inscription');
        $this->dropIndexIfExists('evaluations', 'idx_evaluations_classe_trim_mat_date');

        $this->dropIndexIfExists('inscriptions', 'idx_inscriptions_annee_statut');
        $this->dropIndexIfExists('inscriptions', 'idx_inscriptions_classe_annee_statut');
    }

    private function addIndexIfMissing(string $tableName, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($tableName) || $this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (! Schema::hasTable($tableName) || ! $this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $tableName)
            ->where('index_name', $indexName)
            ->exists();
    }
};
