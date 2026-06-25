<?php

use App\Http\Controllers\Api\Parent\AuthController;
use App\Http\Controllers\Api\Parent\CommunicationController;
use App\Http\Controllers\Api\Parent\EnfantController;
use App\Http\Controllers\Api\Parent\EnfantDetailController;
use Illuminate\Support\Facades\Route;

Route::prefix('parent')->name('api.parent.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::post('/password/forgot', [AuthController::class, 'sendPasswordOtp'])->name('password.forgot');
    Route::post('/password/verify-otp', [AuthController::class, 'verifyPasswordOtp'])->name('password.verify-otp');
    Route::post('/password/reset', [AuthController::class, 'resetPasswordWithOtp'])->name('password.reset');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::put('/me', [AuthController::class, 'updateProfile'])->name('me.update');
        Route::put('/password', [AuthController::class, 'updatePassword'])->name('password.update');

        Route::get('/enfants', [EnfantController::class, 'index'])->name('enfants.index');
        Route::get('/enfants/{eleve}/dashboard', [EnfantController::class, 'dashboard'])->name('enfants.dashboard');
        Route::get('/enfants/{eleve}/filtres', [EnfantDetailController::class, 'filtres'])->name('enfants.filtres');

        Route::get('/enfants/{eleve}/notes', [EnfantDetailController::class, 'notes'])->name('enfants.notes');
        Route::get('/enfants/{eleve}/resultats', [EnfantDetailController::class, 'resultats'])->name('enfants.resultats');

        Route::get('/enfants/{eleve}/bulletins', [EnfantDetailController::class, 'bulletins'])->name('enfants.bulletins');
        Route::get('/enfants/{eleve}/bulletins/annuel/telecharger', [EnfantDetailController::class, 'telechargerBulletinAnnuel'])->name('enfants.bulletins.annuel.telecharger');
        Route::get('/enfants/{eleve}/bulletins/{trimestre}/telecharger', [EnfantDetailController::class, 'telechargerBulletinTrimestriel'])->name('enfants.bulletins.trimestriel.telecharger');

        Route::get('/enfants/{eleve}/paiements', [EnfantDetailController::class, 'paiements'])->name('enfants.paiements');
        Route::get('/paiements/{paiement}/recu', [EnfantDetailController::class, 'recuPaiement'])->name('paiements.recu');

        Route::get('/enfants/{eleve}/paiements-declares', [EnfantDetailController::class, 'paiementsDeclares'])->name('enfants.paiements-declares');
        Route::post('/enfants/{eleve}/paiements-declares', [EnfantDetailController::class, 'declarerPaiement'])->name('enfants.paiements-declares.store');
        Route::get('/paiements-declares/{paiementDeclare}/preuve', [EnfantDetailController::class, 'preuvePaiementDeclare'])->name('paiements-declares.preuve');

        Route::get('/enfants/{eleve}/absences-retards', [EnfantDetailController::class, 'absencesRetards'])->name('enfants.absences-retards');
        Route::post('/absences-retards/{absenceRetard}/justifier', [EnfantDetailController::class, 'justifierAbsenceRetard'])->name('absences-retards.justifier');
        Route::get('/justifications/{justification}/piece', [EnfantDetailController::class, 'pieceJustification'])->name('justifications.piece');

        Route::get('/enfants/{eleve}/sanctions', [EnfantDetailController::class, 'sanctions'])->name('enfants.sanctions');

        Route::get('/enfants/{eleve}/reinscription', [EnfantDetailController::class, 'reinscription'])->name('enfants.reinscription');
        Route::post('/enfants/{eleve}/reinscription', [EnfantDetailController::class, 'demanderReinscription'])->name('enfants.reinscription.store');

        Route::get('/annonces', [CommunicationController::class, 'annonces'])->name('annonces.index');
        Route::get('/annonces/{annonce}', [CommunicationController::class, 'annonce'])->name('annonces.show');

        Route::get('/notifications', [CommunicationController::class, 'notifications'])->name('notifications.index');
        Route::get('/notifications/non-lues', [CommunicationController::class, 'notificationsNonLues'])->name('notifications.non-lues');
        Route::patch('/notifications/tout-lu', [CommunicationController::class, 'toutMarquerCommeLu'])->name('notifications.tout-lu');
        Route::get('/notifications/{notification}', [CommunicationController::class, 'notification'])->name('notifications.show');
        Route::patch('/notifications/{notification}/lue', [CommunicationController::class, 'marquerNotificationLue'])->name('notifications.lue');
    });
});
