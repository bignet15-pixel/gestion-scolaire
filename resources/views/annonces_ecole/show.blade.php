<x-app-layout>
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Annonce officielle</div>
                <h1>{{ $annonce->titre }}</h1>
                <p>{{ $annonce->libelleType() }} — {{ $annonce->libellePriorite() }}</p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('annonces-ecole.index') }}" class="btn">Retour</a>
            </div>
        </div>

        <div class="card">
            <dl class="detail-list">
                <div><dt>Publié le</dt><dd>{{ $annonce->date_publication?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                <div><dt>Classe</dt><dd>{{ $annonce->classe?->nom ?? '-' }}</dd></div>
                <div><dt>Expiration</dt><dd>{{ $annonce->date_expiration?->format('d/m/Y') ?? '-' }}</dd></div>
            </dl>
        </div>

        <div class="card">
            <h2>Contenu de l’annonce</h2>
            <div class="announcement-content">
                {!! nl2br(e($annonce->contenu)) !!}
            </div>
        </div>
    </div>
</x-app-layout>
