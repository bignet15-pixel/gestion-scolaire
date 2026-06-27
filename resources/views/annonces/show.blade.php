<x-app-layout>
    <div class="container communication-page">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="detail-header-card communication-hero">
            <div>
                <div class="detail-kicker">Annonce</div>
                <h1>{{ $annonce->titre }}</h1>
                <p>{{ $annonce->libelleType() }} — {{ $annonce->libelleCible() }}</p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('annonces.index') }}" class="btn">Retour</a>
                <a href="{{ route('annonces.edit', $annonce) }}" class="btn">Modifier</a>

                @if (! $annonce->est_publiee)
                    <form method="POST" action="{{ route('annonces.publier', $annonce) }}" data-confirm="Publier cette annonce et placer les emails dans la file d’attente ?" data-confirm-button="Publier">
                        @csrf
                        <button type="submit" class="btn btn-primary">Publier</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('annonces.destroy', $annonce) }}" data-confirm="Supprimer cette annonce ?" data-confirm-button="Supprimer">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>

        <div class="communication-stats-grid">
            <div class="stat-card"><div class="stat-title">Statut</div><div class="stat-value">{{ $annonce->est_publiee ? 'Publiée' : 'Brouillon' }}</div></div>
            <div class="stat-card"><div class="stat-title">Destinataires</div><div class="stat-value">{{ $stats['destinataires'] }}</div></div>
            <div class="stat-card"><div class="stat-title">Emails en file</div><div class="stat-value">{{ $stats['emails_en_file'] ?? 0 }}</div></div>
            <div class="stat-card"><div class="stat-title">Emails envoyés</div><div class="stat-value">{{ $stats['emails_envoyes'] }}</div></div>
            <div class="stat-card"><div class="stat-title">Emails échoués</div><div class="stat-value">{{ $stats['emails_echoues'] }}</div></div>
        </div>

        <div class="communication-detail-layout">
            <div class="card communication-article-card">
                <div class="communication-card-title">
                    <div>
                        <h2>Contenu de l’annonce</h2>
                        <p>Message complet envoyé aux destinataires.</p>
                    </div>
                </div>

                <div class="announcement-content communication-content-box">
                    {!! nl2br(e($annonce->contenu)) !!}
                </div>
            </div>

            <div class="card communication-side-card">
                <h2>Détails</h2>
                <dl class="detail-list communication-detail-list">
                    <div><dt>Priorité</dt><dd>{{ $annonce->libellePriorite() }}</dd></div>
                    <div><dt>Classe</dt><dd>{{ $annonce->classe?->nom ?? '-' }}</dd></div>
                    <div><dt>Publié par</dt><dd>{{ $annonce->auteur?->name ?? '-' }}</dd></div>
                    <div><dt>Date publication</dt><dd>{{ $annonce->date_publication?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                    <div><dt>Date expiration</dt><dd>{{ $annonce->date_expiration?->format('d/m/Y') ?? '-' }}</dd></div>
                    <div><dt>Lecture</dt><dd>{{ $stats['lues'] ?? 0 }} notification(s) lue(s)</dd></div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
