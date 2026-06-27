<x-app-layout>
    <div class="container communication-page">
        <div class="detail-header-card communication-hero">
            <div>
                <div class="detail-kicker">Annonce officielle</div>
                <h1>{{ $annonce->titre }}</h1>
                <p>{{ $annonce->libelleType() }} — {{ $annonce->libellePriorite() }}</p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('annonces-ecole.index') }}" class="btn">Retour</a>
            </div>
        </div>

        <div class="communication-detail-layout">
            <div class="card communication-article-card">
                <div class="communication-card-title">
                    <div>
                        <h2>Contenu de l’annonce</h2>
                        <p>Message officiel publié par l’école.</p>
                    </div>
                </div>

                <div class="announcement-content communication-content-box">
                    {!! nl2br(e($annonce->contenu)) !!}
                </div>
            </div>

            <div class="card communication-side-card">
                <h2>Informations</h2>
                <dl class="detail-list communication-detail-list">
                    <div><dt>Publié le</dt><dd>{{ $annonce->date_publication?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                    <div><dt>Type</dt><dd>{{ $annonce->libelleType() }}</dd></div>
                    <div><dt>Priorité</dt><dd>{{ $annonce->libellePriorite() }}</dd></div>
                    <div><dt>Classe</dt><dd>{{ $annonce->classe?->nom ?? '-' }}</dd></div>
                    <div><dt>Expiration</dt><dd>{{ $annonce->date_expiration?->format('d/m/Y') ?? '-' }}</dd></div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
