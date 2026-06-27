<?php

namespace App\Http\Controllers;

use App\Models\Annonce;

class AnnonceEcoleController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $annonces = Annonce::with(['auteur', 'classe'])
            ->where('est_publiee', true)
            ->where(function ($query) {
                $query->whereNull('date_expiration')
                    ->orWhere('date_expiration', '>=', now());
            })
            ->whereExists(function ($query) use ($userId) {
                $query->selectRaw('1')
                    ->from('notifications_utilisateurs')
                    ->whereColumn('notifications_utilisateurs.source_id', 'annonces.id')
                    ->where('notifications_utilisateurs.source_type', Annonce::class)
                    ->where('notifications_utilisateurs.type', 'annonce')
                    ->where('notifications_utilisateurs.user_id', $userId)
                    ->whereNull('notifications_utilisateurs.deleted_at');
            })
            ->latest('date_publication')
            ->paginate(15)
            ->withQueryString();

        return view('annonces_ecole.index', compact('annonces'));
    }

    public function show(Annonce $annonce)
    {
        $notification = auth()->user()
            ->notificationsUtilisateur()
            ->where('type', 'annonce')
            ->where('source_type', Annonce::class)
            ->where('source_id', $annonce->id)
            ->first();

        abort_unless(auth()->user()->estGestionnaire() || $notification, 403);

        if ($notification) {
            $notification->marquerCommeLue();
        }

        $annonce->load(['auteur', 'classe']);

        return view('annonces_ecole.show', compact('annonce'));
    }
}
