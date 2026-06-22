<x-app-layout>
    <div class="container request-detail-page">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="detail-header-card request-detail-hero">
            <div>
                <div class="detail-kicker">Demande parentale</div>
                <h1>Demande de réinscription</h1>
                <p>Valide uniquement si la classe demandée respecte les conditions du système.</p>
            </div>
            <a href="{{ route('gestionnaire.demandes-reinscription.index') }}" class="btn">Retour à la liste</a>
        </div>

        <div class="request-detail-grid">
            <div class="card request-detail-main">
                <div class="request-detail-titlebar">
                    <h2>{{ $demande->eleve?->nom }} {{ $demande->eleve?->prenom }}</h2>
                    <span class="status-pill status-{{ $demande->statut }}">{{ $demande->libelleStatut() }}</span>
                </div>

                <div class="info-grid request-info-grid">
                    <div>
                        <strong>Élève</strong>
                        <p>{{ $demande->eleve?->nom }} {{ $demande->eleve?->prenom }}</p>
                        <small>{{ $demande->eleve?->matricule ?? '-' }}</small>
                    </div>
                    <div>
                        <strong>Parent demandeur</strong>
                        <p>{{ $demande->parent?->nom }} {{ $demande->parent?->prenom }}</p>
                        <small>{{ $demande->parent?->phone ?? $demande->parent?->email ?? '-' }}</small>
                    </div>
                    <div>
                        <strong>Ancienne classe</strong>
                        <p>{{ $demande->ancienneClasse?->nom ?? '-' }}</p>
                        <small>{{ $demande->ancienneInscription?->anneeScolaire?->libelle ?? '-' }}</small>
                    </div>
                    <div>
                        <strong>Classe demandée</strong>
                        <p>{{ $demande->classeDemandee?->nom ?? '-' }}</p>
                        <small>{{ $demande->nouvelleAnneeScolaire?->libelle ?? '-' }}</small>
                    </div>
                    <div>
                        <strong>Type de demande</strong>
                        <p>{{ $demande->libelleTypeDemande() }}</p>
                    </div>
                    <div>
                        <strong>Décision système</strong>
                        <p>{{ $demande->libelleDecisionSysteme() }}</p>
                    </div>
                    <div class="info-grid-full">
                        <strong>Commentaire du parent</strong>
                        <p>{{ $demande->commentaire_parent ?? 'Aucun commentaire.' }}</p>
                    </div>
                    <div>
                        <strong>Inscription créée</strong>
                        <p>
                            @if ($demande->inscriptionCreee)
                                <a href="{{ route('inscriptions.show', $demande->inscriptionCreee) }}" class="btn btn-sm">Voir inscription</a>
                            @else
                                Non créée
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="card request-detail-side">
                <h2>Traitement</h2>

                @if ($demande->estEnAttente())
                    <form action="{{ route('gestionnaire.demandes-reinscription.valider', $demande) }}" method="POST" data-confirm="Valider cette réinscription et créer l’inscription officielle ?" class="decision-box decision-box-success">
                        @csrf
                        <h3>Valider</h3>
                        <p>L’inscription officielle sera créée dans la classe demandée.</p>
                        <textarea name="commentaire_gestionnaire" class="form-control" rows="4" placeholder="Commentaire optionnel"></textarea>
                        <button type="submit" class="btn btn-success">Valider</button>
                    </form>

                    <form action="{{ route('gestionnaire.demandes-reinscription.refuser', $demande) }}" method="POST" data-confirm="Refuser cette demande de réinscription ?" class="decision-box decision-box-danger">
                        @csrf
                        <h3>Refuser</h3>
                        <p>Indique clairement pourquoi la demande ne peut pas être acceptée.</p>
                        <textarea name="commentaire_gestionnaire" class="form-control" rows="4" placeholder="Motif du refus" required></textarea>
                        <button type="submit" class="btn btn-danger">Refuser</button>
                    </form>
                @else
                    <div class="processed-box">
                        <strong>Demande déjà traitée</strong>
                        <p>{{ $demande->commentaire_gestionnaire ?? 'Aucun commentaire.' }}</p>
                        <small>
                            Par {{ $demande->validePar?->nom ?? '-' }} {{ $demande->validePar?->prenom ?? '' }}
                            @if ($demande->valide_le)
                                le {{ $demande->valide_le->format('d/m/Y H:i') }}
                            @endif
                        </small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
