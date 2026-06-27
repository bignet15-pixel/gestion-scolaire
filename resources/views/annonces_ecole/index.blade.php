<x-app-layout>
    <div class="container communication-page">
        <div class="detail-header-card communication-hero">
            <div>
                <div class="detail-kicker">Annonces reçues</div>
                <h1>Annonces de l’école</h1>
                <p>Retrouvez les annonces officielles qui concernent votre espace.</p>
            </div>
        </div>

        <div class="announcement-list communication-list">
            @forelse ($annonces as $annonce)
                <div class="card announcement-card communication-received-card">
                    <div class="communication-card-head">
                        <div>
                            <div class="notification-meta">
                                {{ $annonce->libelleType() }} · {{ $annonce->date_publication?->format('d/m/Y H:i') }}
                            </div>
                            <h2>{{ $annonce->titre }}</h2>
                        </div>
                        <span class="badge {{ $annonce->priorite === 'urgente' ? 'badge-danger' : ($annonce->priorite === 'importante' ? 'badge-warning' : 'badge-primary-soft') }}">
                            {{ $annonce->libellePriorite() }}
                        </span>
                    </div>

                    <p>{{ \Illuminate\Support\Str::limit($annonce->contenu, 220) }}</p>

                    <div class="detail-actions">
                        <a href="{{ route('annonces-ecole.show', $annonce) }}" class="btn btn-primary">Lire l’annonce</a>
                    </div>
                </div>
            @empty
                <div class="card empty-state">Aucune annonce disponible pour le moment.</div>
            @endforelse
        </div>

        {{ $annonces->links() }}
    </div>
</x-app-layout>
