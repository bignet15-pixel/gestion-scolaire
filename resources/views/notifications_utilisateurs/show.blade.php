<x-app-layout>
    <div class="container communication-page">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="detail-header-card communication-hero">
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

        <div class="communication-detail-layout">
            <div class="card communication-article-card">
                <div class="communication-card-title">
                    <div>
                        <h2>Détail de la notification</h2>
                        <p>Message complet envoyé à votre compte.</p>
                    </div>
                </div>

                <div class="announcement-content communication-content-box">
                    {!! nl2br(e($notification->message)) !!}
                </div>
            </div>

            <div class="card communication-side-card">
                <h2>Suivi</h2>
                <dl class="detail-list communication-detail-list">
                    <div><dt>Statut lecture</dt><dd>{{ $notification->lue ? 'Lue' : 'Non lue' }}</dd></div>
                    <div><dt>Lue le</dt><dd>{{ $notification->lue_le?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $notification->email_statut }}</dd></div>
                    <div><dt>Email envoyé le</dt><dd>{{ $notification->email_envoye_le?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
