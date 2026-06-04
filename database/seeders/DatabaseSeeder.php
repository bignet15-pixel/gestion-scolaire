<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\ClasseMatiereUser;
use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\Paiement;
use App\Models\Trimestre;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Remplit la base avec les données de départ du projet.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Création du gestionnaire
        |--------------------------------------------------------------------------
        */

        $gestionnaire = User::create([
            'name' => 'KIEDEGA Abdoul-samando',
            'nom' => 'KIEDEGA',
            'prenom' => 'Abdoul-samando',
            'sexe' => 'M',
            'email' => 'gestionnaire@example.com',
            'phone' => '70000001',
            'password' => Hash::make('password'),
            'role' => 'gestionnaire',
            'adresse' => 'Ouagadougou',
            'matricule' => 'GES-0001',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. Création des enseignants
        |--------------------------------------------------------------------------
        */

        $enseignant1 = User::create([
            'name' => 'KABORE Mohamadi',
            'nom' => 'KABORE',
            'prenom' => 'Mohamadi',
            'sexe' => 'M',
            'email' => 'enseignant1@example.com',
            'phone' => '70000002',
            'password' => Hash::make('password'),
            'role' => 'enseignant',
            'adresse' => 'Ouagadougou',
            'matricule' => 'ENS-0001',
        ]);

        $enseignant2 = User::create([
            'name' => 'TRAORE Aïcha',
            'nom' => 'TRAORE',
            'prenom' => 'Aïcha',
            'sexe' => 'F',
            'email' => 'enseignant2@example.com',
            'phone' => '70000003',
            'password' => Hash::make('password'),
            'role' => 'enseignant',
            'adresse' => 'Ouagadougou',
            'matricule' => 'ENS-0002',
        ]);

        $enseignant3 = User::create([
            'name' => 'OUEDRAOGO Edite',
            'nom' => 'OUEDRAOGO',
            'prenom' => 'Edite',
            'sexe' => 'F',
            'email' => 'enseignant3@example.com',
            'phone' => '70000004',
            'password' => Hash::make('password'),
            'role' => 'enseignant',
            'adresse' => 'Ouagadougou',
            'matricule' => 'ENS-0003',
        ]);

        $enseignant4 = User::create([
            'name' => 'SAWADOGO Paul',
            'nom' => 'SAWADOGO',
            'prenom' => 'Paul',
            'sexe' => 'M',
            'email' => 'enseignant4@example.com',
            'phone' => '70000005',
            'password' => Hash::make('password'),
            'role' => 'enseignant',
            'adresse' => 'Ouagadougou',
            'matricule' => 'ENS-0004',
        ]);

        $enseignant5 = User::create([
            'name' => 'SOME Mariam',
            'nom' => 'SOME',
            'prenom' => 'Mariam',
            'sexe' => 'F',
            'email' => 'enseignant5@example.com',
            'phone' => '70000006',
            'password' => Hash::make('password'),
            'role' => 'enseignant',
            'adresse' => 'Koudougou',
            'matricule' => 'ENS-0005',
        ]);

        $enseignant6 = User::create([
            'name' => 'BARRY Issa',
            'nom' => 'BARRY',
            'prenom' => 'Issa',
            'sexe' => 'M',
            'email' => 'enseignant6@example.com',
            'phone' => '70000007',
            'password' => Hash::make('password'),
            'role' => 'enseignant',
            'adresse' => 'Bobo Dioulasso',
            'matricule' => 'ENS-0006',
        ]);

        $enseignant7 = User::create([
            'name' => 'NIKIEMA Rosalie',
            'nom' => 'NIKIEMA',
            'prenom' => 'Rosalie',
            'sexe' => 'F',
            'email' => 'enseignant7@example.com',
            'phone' => '70000008',
            'password' => Hash::make('password'),
            'role' => 'enseignant',
            'adresse' => 'Ouahigouya',
            'matricule' => 'ENS-0007',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 3. Création de l'année scolaire active
        |--------------------------------------------------------------------------
        */

        $annee = AnneeScolaire::create([
            'libelle' => '2025-2026',
            'date_debut' => '2025-10-01',
            'date_fin' => '2026-07-31',
            'statut' => 'active',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 4. Création des trimestres
        |--------------------------------------------------------------------------
        */

        $trimestre1 = Trimestre::create([
            'annee_scolaire_id' => $annee->id,
            'nom' => 'Trimestre 1',
            'date_debut' => '2025-10-01',
            'date_fin' => '2025-12-20',
            'statut' => 'actif',
        ]);

        $trimestre2 = Trimestre::create([
            'annee_scolaire_id' => $annee->id,
            'nom' => 'Trimestre 2',
            'date_debut' => '2026-01-05',
            'date_fin' => '2026-03-31',
            'statut' => 'actif',
        ]);

        $trimestre3 = Trimestre::create([
            'annee_scolaire_id' => $annee->id,
            'nom' => 'Trimestre 3',
            'date_debut' => '2026-04-01',
            'date_fin' => '2026-07-31',
            'statut' => 'actif',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 5. Création des matières
        |--------------------------------------------------------------------------
        */

        $francais = Matiere::create([
            'nom' => 'Français',
            'coefficient_default' => 1,
        ]);

        $mathematiques = Matiere::create([
            'nom' => 'Mathématiques',
            'coefficient_default' => 1,
        ]);

        $sciences = Matiere::create([
            'nom' => 'Sciences',
            'coefficient_default' => 1,
        ]);

        $histoire = Matiere::create([
            'nom' => 'Histoire-Géographie',
            'coefficient_default' => 1,
        ]);

        $lecture = Matiere::create([
            'nom' => 'Lecture',
            'coefficient_default' => 1,
        ]);

        $ecriture = Matiere::create([
            'nom' => 'Écriture',
            'coefficient_default' => 1,
        ]);

        $dessin = Matiere::create([
            'nom' => 'Dessin',
            'coefficient_default' => 1,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 6. Création des classes
        |--------------------------------------------------------------------------
        */

        $cp1 = Classe::create([
            'annee_scolaire_id' => $annee->id,
            'enseignant_principal_id' => $enseignant1->id,
            'niveau' => 'CP1',
            'nom' => 'CP1 A',
            'frais_scolarite' => 75000,
        ]);

        Classe::create([
            'annee_scolaire_id' => $annee->id,
            'enseignant_principal_id' => $enseignant2->id,
            'niveau' => 'CP2',
            'nom' => 'CP2 A',
            'frais_scolarite' => 75000,
        ]);

        Classe::create([
            'annee_scolaire_id' => $annee->id,
            'enseignant_principal_id' => $enseignant3->id,
            'niveau' => 'CE1',
            'nom' => 'CE1 A',
            'frais_scolarite' => 80000,
        ]);

        Classe::create([
            'annee_scolaire_id' => $annee->id,
            'enseignant_principal_id' => $enseignant4->id,
            'niveau' => 'CE2',
            'nom' => 'CE2 A',
            'frais_scolarite' => 80000,
        ]);

        Classe::create([
            'annee_scolaire_id' => $annee->id,
            'enseignant_principal_id' => $enseignant5->id,
            'niveau' => 'CM1',
            'nom' => 'CM1 A',
            'frais_scolarite' => 90000,
        ]);

        Classe::create([
            'annee_scolaire_id' => $annee->id,
            'enseignant_principal_id' => $enseignant6->id,
            'niveau' => 'CM2',
            'nom' => 'CM2 A',
            'frais_scolarite' => 90000,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 7. Création des élèves de CP1
        |--------------------------------------------------------------------------
        */

        $eleveCp1A = Eleve::create([
            'matricule' => 'ELV-001',
            'nom' => 'OUEDRAOGO',
            'prenom' => 'Moussa',
            'sexe' => 'M',
            'date_naissance' => '2019-03-12',
            'lieu_naissance' => 'Ouagadougou',
            'contact_parent' => '71000001',
        ]);

        $eleveCp1B = Eleve::create([
            'matricule' => 'ELV-002',
            'nom' => 'KABORE',
            'prenom' => 'Aminata',
            'sexe' => 'F',
            'date_naissance' => '2019-08-24',
            'lieu_naissance' => 'Koudougou',
            'contact_parent' => '71000002',
        ]);

        $eleveCp1C = Eleve::create([
            'matricule' => 'ELV-003',
            'nom' => 'KABORE',
            'prenom' => 'Abdoul',
            'sexe' => 'M',
            'date_naissance' => '2019-07-20',
            'lieu_naissance' => 'Ouahigouya',
            'contact_parent' => '71000003',
        ]);

        $eleveCp1D = Eleve::create([
            'matricule' => 'ELV-004',
            'nom' => 'KIEDEGA',
            'prenom' => 'Adama',
            'sexe' => 'M',
            'date_naissance' => '2018-07-21',
            'lieu_naissance' => 'Bobo Dioulasso',
            'contact_parent' => '71000004',
        ]);

        $eleveCp1E = Eleve::create([
            'matricule' => 'ELV-005',
            'nom' => 'Zongo',
            'prenom' => 'Alfred',
            'sexe' => 'M',
            'date_naissance' => '2018-07-20',
            'lieu_naissance' => 'Fada',
            'contact_parent' => '71000005',
        ]);

        $eleveCp1F = Eleve::create([
            'matricule' => 'ELV-006',
            'nom' => 'SANKARA',
            'prenom' => 'Fatoumata',
            'sexe' => 'F',
            'date_naissance' => '2019-01-18',
            'lieu_naissance' => 'Ouagadougou',
            'contact_parent' => '71000006',
        ]);

        $eleveCp1G = Eleve::create([
            'matricule' => 'ELV-007',
            'nom' => 'TRAORE',
            'prenom' => 'Issouf',
            'sexe' => 'M',
            'date_naissance' => '2018-11-09',
            'lieu_naissance' => 'Kaya',
            'contact_parent' => '71000007',
        ]);

        $eleveCp1H = Eleve::create([
            'matricule' => 'ELV-008',
            'nom' => 'SOME',
            'prenom' => 'Clarisse',
            'sexe' => 'F',
            'date_naissance' => '2019-05-14',
            'lieu_naissance' => 'Banfora',
            'contact_parent' => '71000008',
        ]);

        $eleveCp1I = Eleve::create([
            'matricule' => 'ELV-009',
            'nom' => 'BARRY',
            'prenom' => 'Oumar',
            'sexe' => 'M',
            'date_naissance' => '2018-12-02',
            'lieu_naissance' => 'Dori',
            'contact_parent' => '71000009',
        ]);

        $eleveCp1J = Eleve::create([
            'matricule' => 'ELV-010',
            'nom' => 'NIKIEMA',
            'prenom' => 'Awa',
            'sexe' => 'F',
            'date_naissance' => '2019-06-27',
            'lieu_naissance' => 'Tenkodogo',
            'contact_parent' => '71000010',
        ]);

        $inscriptionsCp1 = collect();

        foreach ([
            $eleveCp1A,
            $eleveCp1B,
            $eleveCp1C,
            $eleveCp1D,
            $eleveCp1E,
            $eleveCp1F,
            $eleveCp1G,
            $eleveCp1H,
            $eleveCp1I,
            $eleveCp1J,
        ] as $eleveCp1) {
            $inscriptionsCp1->push(Inscription::create([
                'eleve_id' => $eleveCp1->id,
                'classe_id' => $cp1->id,
                'annee_scolaire_id' => $annee->id,
                'date_inscription' => '2025-10-01',
                'frais_attendu' => $cp1->frais_scolarite,
                'statut' => 'actif',
            ]));
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Affectations enseignant / classe / matière
        |--------------------------------------------------------------------------
        */

        ClasseMatiereUser::create([
            'classe_id' => $cp1->id,
            'matiere_id' => $mathematiques->id,
            'user_id' => $enseignant3->id,
            'date_debut' => '2025-10-01',
            'statut' => 'actif',
        ]);

        ClasseMatiereUser::create([
            'classe_id' => $cp1->id,
            'matiere_id' => $francais->id,
            'user_id' => $enseignant2->id,
            'date_debut' => '2025-10-01',
            'statut' => 'actif',
        ]);

        ClasseMatiereUser::create([
            'classe_id' => $cp1->id,
            'matiere_id' => $sciences->id,
            'user_id' => $enseignant1->id,
            'date_debut' => '2025-10-01',
            'statut' => 'actif',
        ]);

        ClasseMatiereUser::create([
            'classe_id' => $cp1->id,
            'matiere_id' => $histoire->id,
            'user_id' => $enseignant4->id,
            'date_debut' => '2025-10-01',
            'statut' => 'actif',
        ]);

        ClasseMatiereUser::create([
            'classe_id' => $cp1->id,
            'matiere_id' => $lecture->id,
            'user_id' => $enseignant5->id,
            'date_debut' => '2025-10-01',
            'statut' => 'actif',
        ]);

        ClasseMatiereUser::create([
            'classe_id' => $cp1->id,
            'matiere_id' => $ecriture->id,
            'user_id' => $enseignant6->id,
            'date_debut' => '2025-10-01',
            'statut' => 'actif',
        ]);

        ClasseMatiereUser::create([
            'classe_id' => $cp1->id,
            'matiere_id' => $dessin->id,
            'user_id' => $enseignant7->id,
            'date_debut' => '2025-10-01',
            'statut' => 'actif',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 9. Compositions de CP1 A pour les trois trimestres
        |--------------------------------------------------------------------------
        */

        $matieresCp1 = [
            $francais,
            $mathematiques,
            $sciences,
            $histoire,
            $lecture,
            $ecriture,
            $dessin,
        ];

        $trimestresCp1 = [
            [
                'trimestre' => $trimestre1,
                'date' => '2025-12-10',
                'nom' => 'composition-1-E',
            ],
            [
                'trimestre' => $trimestre2,
                'date' => '2026-03-20',
                'nom' => 'composition-2-E',
            ],
            [
                'trimestre' => $trimestre3,
                'date' => '2026-06-20',
                'nom' => 'composition-3-E',
            ],
        ];

        $evaluationsCp1 = collect();

        foreach ($trimestresCp1 as $periode) {
            foreach ($matieresCp1 as $index => $matiere) {
                $evaluationsCp1->push(Evaluation::create([
                    'classe_id' => $cp1->id,
                    'matiere_id' => $matiere->id,
                    'trimestre_id' => $periode['trimestre']->id,
                    'user_id' => $enseignant1->id,
                    'nom' => $periode['nom'],
                    'type' => 'composition',
                    'date_evaluation' => $periode['date'],
                    'heure_debut' => sprintf('%02d:00', 8 + $index),
                    'heure_fin' => sprintf('%02d:45', 8 + $index),
                    'coefficient' => 1,
                    'bareme' => 20,
                ]));
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Paiements des élèves de CP1 A
        |--------------------------------------------------------------------------
        */

        $paiementsCp1 = [
            75000,
            75000,
            50000,
            60000,
            75000,
            30000,
            75000,
            45000,
            75000,
            20000,
        ];

        foreach ($inscriptionsCp1 as $index => $inscription) {
            Paiement::create([
                'inscription_id' => $inscription->id,
                'user_id' => $gestionnaire->id,
                'numero_paiement' => 'REC-2025-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'montant' => $paiementsCp1[$index],
                'date_paiement' => '2025-10-' . str_pad((string) ($index + 2), 2, '0', STR_PAD_LEFT),
                'mode_paiement' => $index % 2 === 0 ? 'especes' : 'mobile_money',
                'contact_parent' => $inscription->eleve?->contact_parent,
                'contact_gestionnaire' => $gestionnaire->phone,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Notes des compositions de CP1 A
        |--------------------------------------------------------------------------
        */

        foreach ($evaluationsCp1 as $evaluationIndex => $evaluation) {
            foreach ($inscriptionsCp1 as $inscriptionIndex => $inscription) {
                $valeur = 8 + (($inscriptionIndex + $evaluationIndex) % 11);

                Note::create([
                    'inscription_id' => $inscription->id,
                    'evaluation_id' => $evaluation->id,
                    'valeur' => $valeur,
                ]);
            }
        }
    }
}
