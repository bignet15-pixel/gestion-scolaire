<x-app-layout>
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Annonce</div>
                <h1>{{ $annonce->titre }}</h1>
                <p>{{ $annonce->libelleType() }} — {{ $annonce->libelleCible() }}</p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('annonces.index') }}" class="btn">Retour</a>
                <a href="{{ route('annonces.edit', $annonce) }}" class="btn">Modifier</a>

                @if (! $annonce->est_publiee)
                    <form method="POST" action="{{ route('annonces.publier', $annonce) }}" data-confirm="Publier cette annonce et envoyer les emails aux destinataires ?" data-confirm-button="Publier">
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

        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-title">Statut</div>
                <div class="stat-value">{{ $annonce->est_publiee ? 'Publiée' : 'Brouillon' }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Destinataires</div>
                <div class="stat-value">{{ $stats['destinataires'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Emails envoyés</div>
                <div class="stat-value">{{ $stats['emails_envoyes'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Emails échoués</div>
                <div class="stat-value">{{ $stats['emails_echoues'] }}</div>
            </div>
        </div>

        <div class="card">
            <h2>Détails</h2>
            <dl class="detail-list">
                <div><dt>Priorité</dt><dd>{{ $annonce->libellePriorite() }}</dd></div>
                <div><dt>Classe</dt><dd>{{ $annonce->classe?->nom ?? '-' }}</dd></div>
                <div><dt>Publié par</dt><dd>{{ $annonce->auteur?->name ?? '-' }}</dd></div>
                <div><dt>Date publication</dt><dd>{{ $annonce->date_publication?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                <div><dt>Date expiration</dt><dd>{{ $annonce->date_expiration?->format('d/m/Y') ?? '-' }}</dd></div>
            </dl>
        </div>

        <div class="card">
            <h2>Contenu envoyé par email</h2>
            <div class="announcement-content">
                {!! nl2br(e($annonce->contenu)) !!}
            </div>
        </div>
    </div>
</x-app-layout>
