<x-app-layout>
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Configuration de sanction</div>
                <h1>{{ $sanction->nom }}</h1>
                <p>{{ ucfirst($sanction->categorie) }} / {{ ucfirst($sanction->mode_declenchement) }}</p>
            </div>
            <div class="detail-actions">
                <a href="{{ route('sanctions.index') }}" class="btn">Retour</a>
                <a href="{{ route('sanctions.edit', $sanction) }}" class="btn btn-primary">Modifier</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="profile-grid">
                <div class="profile-row"><span>Catégorie</span><strong>{{ ucfirst($sanction->categorie) }}</strong></div>
                <div class="profile-row"><span>Mode</span><strong>{{ ucfirst($sanction->mode_declenchement) }}</strong></div>
                <div class="profile-row"><span>Statut déclencheur</span><strong>{{ ucfirst(str_replace('_', ' ', $sanction->statut_declencheur)) }}</strong></div>
                <div class="profile-row"><span>Seuil</span><strong>{{ $sanction->seuil ?? '-' }}</strong></div>
                <div class="profile-row"><span>Période de calcul</span><strong>{{ $sanction->periode_calcul ? ucfirst($sanction->periode_calcul) : '-' }}</strong></div>
                <div class="profile-row"><span>Gravité</span><strong>{{ ucfirst($sanction->niveau_gravite) }}</strong></div>
                <div class="profile-row"><span>Effet</span><strong>{{ ucfirst(str_replace('_', ' ', $sanction->type_effet)) }}</strong></div>
                <div class="profile-row"><span>Valeur</span><strong>{{ $sanction->valeur_effet ?? '-' }}</strong></div>
                <div class="profile-row"><span>Active</span><strong>{{ $sanction->active ? 'Oui' : 'Non' }}</strong></div>
                <div class="profile-row"><span>Visible parent par défaut</span><strong>{{ $sanction->visible_parent_defaut ? 'Oui' : 'Non' }}</strong></div>
                <div class="profile-row"><span>Créée par</span><strong>{{ $sanction->createdBy?->name ?? '-' }}</strong></div>
                <div class="profile-row"><span>Utilisations</span><strong>{{ $sanction->sanctionsAppliquees->count() }}</strong></div>
            </div>

            <div class="detail-text-grid">
                <section class="detail-text-section detail-text-section-wide">
                    <h2>Description</h2>
                    <p>{{ $sanction->description ?: 'Aucune description.' }}</p>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
