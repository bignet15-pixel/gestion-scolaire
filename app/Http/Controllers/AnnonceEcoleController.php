<?php

namespace App\Http\Controllers;

use App\Models\Annonce;

class AnnonceEcoleController extends Controller
{
    public function index()
    {
        $annonceIds = auth()->user()
            ->notificationsUtilisateur()
            ->where('type', 'annonce')
            ->where('source_type', Annonce::class)
            ->pluck('source_id');

        $annonces = Annonce::with(['auteur', 'classe'])
            ->whereIn('id', $annonceIds)
            ->where('est_publiee', true)
            ->where(function ($query) {
                $query->whereNull('date_expiration')
                    ->orWhere('date_expiration', '>=', now());
            })
            ->latest('date_publication')
            ->paginate(15);

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
