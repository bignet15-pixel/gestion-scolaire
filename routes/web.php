<?php

use App\Http\Controllers\AbsenceRetardController;
use App\Http\Controllers\AnneeScolaireController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\ClasseMatiereUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EleveController;
use App\Http\Controllers\EmploiDuTempsController;
use App\Http\Controllers\EnseignantController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\ImpayeController;
use App\Http\Controllers\InscriptionController;
use App\Http\Controllers\MatiereController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResultatController;
use App\Http\Controllers\SanctionAppliqueeController;
use App\Http\Controllers\SanctionController;
use App\Http\Controllers\TrimestreController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:gestionnaire'])->group(function () {
    Route::resource('annee-scolaires', AnneeScolaireController::class)
        ->except(['show']);

    Route::patch('/annee-scolaires/{annee_scolaire}/activer', [AnneeScolaireController::class, 'activer'])
        ->name('annee-scolaires.activer');

    Route::patch('/annee-scolaires/{annee_scolaire}/fermer', [AnneeScolaireController::class, 'fermer'])
        ->name('annee-scolaires.fermer');

    Route::resource('trimestres', TrimestreController::class)
        ->except(['show']);

    Route::patch('/trimestres/{trimestre}/fermer', [TrimestreController::class, 'fermer'])
        ->name('trimestres.fermer');

    Route::patch('/trimestres/{trimestre}/activer', [TrimestreController::class, 'activer'])
        ->name('trimestres.activer');

    Route::resource('matieres', MatiereController::class)
        ->except(['show']);

    Route::resource('classes', ClasseController::class)
        ->parameters(['classes' => 'classe']);

    Route::get('/classes/{classe}/eleves/pdf', [ClasseController::class, 'imprimerEleves'])
        ->name('classes.eleves-pdf');

    Route::resource('enseignants', EnseignantController::class)
        ->parameters(['enseignants' => 'enseignant']);

    Route::resource('affectations', ClasseMatiereUserController::class)
        ->except(['show'])
        ->parameters(['affectations' => 'affectation']);

    Route::patch('/affectations/{affectation}/terminer', [ClasseMatiereUserController::class, 'terminer'])
        ->name('affectations.terminer');

    Route::patch('/affectations/{affectation}/suspendre', [ClasseMatiereUserController::class, 'suspendre'])
        ->name('affectations.suspendre');

    Route::patch('/affectations/{affectation}/reactiver', [ClasseMatiereUserController::class, 'reactiver'])
        ->name('affectations.reactiver');

    Route::resource('eleves', EleveController::class)
        ->parameters(['eleves' => 'eleve']);

    Route::get('/inscriptions/{inscription}/trimestres/{trimestre}/bulletin', [BulletinController::class, 'trimestriel'])
        ->name('bulletins.trimestriel');

    Route::get('/inscriptions/{inscription}/bulletin-annuel', [BulletinController::class, 'annuel'])
        ->name('bulletins.annuel');

    Route::get('/inscriptions/options', [InscriptionController::class, 'options'])
        ->name('inscriptions.options');

    Route::resource('inscriptions', InscriptionController::class)
        ->parameters(['inscriptions' => 'inscription']);

    Route::resource('paiements', PaiementController::class)
        ->parameters(['paiements' => 'paiement']);

    Route::get('/paiements/{paiement}/recu', [PaiementController::class, 'recu'])
        ->name('paiements.recu');

    Route::get('/impayes', [ImpayeController::class, 'index'])
        ->name('impayes.index');

    Route::resource('emplois-du-temps', EmploiDuTempsController::class)
        ->parameters(['emplois-du-temps' => 'emploi_du_temps']);

    Route::resource('sanctions', SanctionController::class)
        ->parameters(['sanctions' => 'sanction']);

    Route::get('/sanctions-appliquees/create', [SanctionAppliqueeController::class, 'create'])
        ->name('sanctions-appliquees.create');

    Route::post('/sanctions-appliquees', [SanctionAppliqueeController::class, 'store'])
        ->name('sanctions-appliquees.store');

    Route::post('/sanctions-appliquees/{sanction_appliquee}/appliquer', [SanctionAppliqueeController::class, 'appliquer'])
        ->name('sanctions-appliquees.appliquer');

    Route::post('/sanctions-appliquees/{sanction_appliquee}/ignorer', [SanctionAppliqueeController::class, 'ignorer'])
        ->name('sanctions-appliquees.ignorer');

    Route::post('/sanctions-appliquees/{sanction_appliquee}/annuler', [SanctionAppliqueeController::class, 'annuler'])
        ->name('sanctions-appliquees.annuler');

    Route::post('/sanctions-appliquees/{sanction_appliquee}/terminer', [SanctionAppliqueeController::class, 'terminer'])
        ->name('sanctions-appliquees.terminer');
});

Route::middleware(['auth', 'role:gestionnaire,enseignant'])->group(function () {
    Route::resource('absences-retards', AbsenceRetardController::class)
        ->parameters(['absences-retards' => 'absence_retard']);

    Route::get('/sanctions-appliquees', [SanctionAppliqueeController::class, 'index'])
        ->name('sanctions-appliquees.index');

    Route::get('/sanctions-appliquees/{sanction_appliquee}', [SanctionAppliqueeController::class, 'show'])
        ->name('sanctions-appliquees.show');

    Route::resource('evaluations', EvaluationController::class)
        ->parameters(['evaluations' => 'evaluation']);

    Route::get('/planning/classe/semaine', [EmploiDuTempsController::class, 'semaineClasse'])
        ->name('emplois-du-temps.semaine-classe');

    Route::get('/planning/classe/semaine/pdf', [EmploiDuTempsController::class, 'imprimerSemaineClasse'])
        ->name('emplois-du-temps.semaine-classe-pdf');

    Route::get('/planning/enseignant/semaine', [EmploiDuTempsController::class, 'semaineEnseignant'])
        ->name('emplois-du-temps.semaine-enseignant');

    Route::get('/planning/enseignant/semaine/pdf', [EmploiDuTempsController::class, 'imprimerSemaineEnseignant'])
        ->name('emplois-du-temps.semaine-enseignant-pdf');

    Route::get('/evaluations/{evaluation}/notes', [NoteController::class, 'saisie'])
        ->name('notes.saisie');

    Route::post('/evaluations/{evaluation}/notes', [NoteController::class, 'enregistrer'])
        ->name('notes.enregistrer');

    Route::get('/resultats', [ResultatController::class, 'index'])
        ->name('resultats.index');
});

Route::middleware(['auth', 'role:enseignant'])->group(function () {
    Route::get('/mes-classes', [ClasseController::class, 'mesClasses'])
        ->name('enseignant.classes.index');

    Route::get('/mes-classes/{classe}', [ClasseController::class, 'showEnseignant'])
        ->name('enseignant.classes.show');

    Route::get('/mes-classes/{classe}/eleves/pdf', [ClasseController::class, 'imprimerEleves'])
        ->name('enseignant.classes.eleves-pdf');
});

require __DIR__.'/auth.php';
