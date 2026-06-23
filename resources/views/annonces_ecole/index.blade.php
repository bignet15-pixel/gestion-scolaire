<x-app-layout>
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Annonces</div>
                <h1>Annonces de l’école</h1>
                <p>Retrouvez ici les annonces officielles qui vous concernent.</p>
            </div>
        </div>

        <div class="announcement-list">
            @forelse ($annonces as $annonce)
                <div class="card announcement-card">
                    <div class="notification-meta">
                        {{ $annonce->libelleType() }} · {{ $annonce->date_publication?->format('d/m/Y H:i') }} · {{ $annonce->libellePriorite() }}
                    </div>

                    <h2>{{ $annonce->titre }}</h2>
                    <p>{{ \Illuminate\Support\Str::limit($annonce->contenu, 220) }}</p>

                    <div class="detail-actions">
                        <a href="{{ route('annonces-ecole.show', $annonce) }}" class="btn btn-primary">Lire l’annonce</a>
                    </div>
                </div>
            @empty
                <div class="card">Aucune annonce disponible pour le moment.</div>
            @endforelse
        </div>

        {{ $annonces->links() }}
    </div>
</x-app-layout>
