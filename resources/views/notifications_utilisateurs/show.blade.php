<x-app-layout>
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">{{ $notification->libelleType() }}</div>
                <h1>{{ $notification->titre }}</h1>
                <p>Notification reçue le {{ $notification->created_at?->format('d/m/Y à H:i') }}.</p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('notifications.index') }}" class="btn">Retour</a>

                @if ($notification->type === 'annonce' && $notification->source)
                    <a href="{{ route('annonces-ecole.show', $notification->source) }}" class="btn btn-primary">Voir l’annonce</a>
                @elseif ($notification->lien)
                    <a href="{{ url($notification->lien) }}" class="btn btn-primary">Ouvrir la page concernée</a>
                @endif
            </div>
        </div>

        <div class="card">
            <h2>Détail</h2>
            <div class="announcement-content">
                {!! nl2br(e($notification->message)) !!}
            </div>
        </div>

        <div class="card">
            <h2>Suivi</h2>
            <dl class="detail-list">
                <div><dt>Statut lecture</dt><dd>{{ $notification->lue ? 'Lue' : 'Non lue' }}</dd></div>
                <div><dt>Lue le</dt><dd>{{ $notification->lue_le?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                <div><dt>Email</dt><dd>{{ $notification->email_statut }}</dd></div>
                <div><dt>Email envoyé le</dt><dd>{{ $notification->email_envoye_le?->format('d/m/Y H:i') ?? '-' }}</dd></div>
            </dl>
        </div>
    </div>
</x-app-layout>
