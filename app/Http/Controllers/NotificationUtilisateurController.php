<?php

namespace App\Http\Controllers;

use App\Models\NotificationUtilisateur;
use Illuminate\Http\Request;

class NotificationUtilisateurController extends Controller
{
    public function index(Request $request)
    {
        $notifications = auth()->user()
            ->notificationsUtilisateur()
            ->with('source')
            ->when($request->input('statut') === 'non_lues', fn ($query) => $query->nonLues())
            ->when($request->input('statut') === 'lues', fn ($query) => $query->lues())
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('notifications_utilisateurs.index', [
            'notifications' => $notifications,
            'types' => NotificationUtilisateur::TYPES,
        ]);
    }

    public function show(NotificationUtilisateur $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->load('source');
        $notification->marquerCommeLue();

        return view('notifications_utilisateurs.show', compact('notification'));
    }

    public function marquerCommeLue(NotificationUtilisateur $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->marquerCommeLue();

        return back()->with('success', 'Notification marquée comme lue.');
    }

    public function toutMarquerCommeLu()
    {
        auth()->user()
            ->notificationsUtilisateur()
            ->nonLues()
            ->update([
                'lue' => true,
                'lue_le' => now(),
            ]);

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }
}
