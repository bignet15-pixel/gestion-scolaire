<x-app-layout>
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Assiduité</div>
                <h1>{{ $evenement->libelleType() }} — {{ $evenement->inscription?->eleve?->nom }} {{ $evenement->inscription?->eleve?->prenom }}</h1>
                <p>{{ $evenement->inscription?->classe?->nom }} / {{ $evenement->inscription?->anneeScolaire?->libelle }}</p>
            </div>
            <div class="detail-actions">
                <a href="{{ route('absences-retards.index', ['annee_scolaire_id' => $evenement->inscription?->annee_scolaire_id, 'classe_id' => $evenement->inscription?->classe_id]) }}" class="btn">Retour</a>
                @if (auth()->user()->estGestionnaire() && ! $evenement->inscription?->anneeScolaire?->estFermee())
                    <a href="{{ route('absences-retards.edit', $evenement) }}" class="btn btn-primary">Modifier</a>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="profile-grid">
                <div class="profile-row"><span>Date début</span><strong>{{ $evenement->date_debut?->format('d/m/Y') }}</strong></div>
                <div class="profile-row"><span>Date fin</span><strong>{{ $evenement->date_fin?->format('d/m/Y') ?? '-' }}</strong></div>
                <div class="profile-row"><span>Période</span><strong>{{ $evenement->libellePeriode() }}</strong></div>
                <div class="profile-row"><span>Heure prévue</span><strong>{{ $evenement->heure_debut?->format('H:i') ?? '-' }}</strong></div>
                <div class="profile-row"><span>Heure fin</span><strong>{{ $evenement->heure_fin?->format('H:i') ?? '-' }}</strong></div>
                <div class="profile-row"><span>Heure arrivée</span><strong>{{ $evenement->heure_arrivee?->format('H:i') ?? '-' }}</strong></div>
                <div class="profile-row"><span>Durée</span><strong>{{ $evenement->duree_minutes ? $evenement->duree_minutes . ' minutes' : '-' }}</strong></div>
                <div class="profile-row"><span>Statut</span><strong>{{ $evenement->libelleStatut() }}</strong></div>
                <div class="profile-row"><span>Catégorie motif</span><strong>{{ ucfirst(str_replace('_', ' ', $evenement->categorie_motif)) }}</strong></div>
                <div class="profile-row"><span>Visible parent</span><strong>{{ $evenement->visible_parent ? 'Oui' : 'Non' }}</strong></div>
                <div class="profile-row"><span>Source</span><strong>{{ ucfirst($evenement->source_signalement) }}</strong></div>
                <div class="profile-row"><span>Enregistré par</span><strong>{{ $evenement->enregistrePar?->name ?? '-' }}</strong></div>
            </div>

            <div class="detail-text-grid">
                <section class="detail-text-section">
                    <h2>Motif</h2>
                    <p>{{ $evenement->motif ?: 'Non renseigné' }}</p>
                </section>

                <section class="detail-text-section">
                    <h2>Justification</h2>
                    <p>{{ $evenement->justification ?: 'Non renseignée' }}</p>
                </section>

                @if (auth()->user()->estGestionnaire())
                    <section class="detail-text-section detail-text-section-wide">
                        <h2>Commentaire interne</h2>
                        <p>{{ $evenement->commentaire_interne ?: 'Aucun commentaire interne.' }}</p>
                    </section>
                @endif
            </div>

            @if ($evenement->piece_justificative)
                <div class="detail-footer-actions">
                    <a href="{{ asset('storage/' . $evenement->piece_justificative) }}" class="btn btn-success" target="_blank">Consulter la pièce justificative</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
