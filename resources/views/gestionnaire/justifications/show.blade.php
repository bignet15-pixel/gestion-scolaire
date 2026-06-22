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
                <h1>Justification d’absence ou retard</h1>
                <p>Consulte la demande du parent avant d’accepter ou de refuser.</p>
            </div>
            <a href="{{ route('gestionnaire.justifications-parent.index') }}" class="btn">Retour à la liste</a>
        </div>

        <div class="request-detail-grid">
            <div class="card request-detail-main">
                <div class="request-detail-titlebar">
                    <h2>{{ $justification->motif }}</h2>
                    <span class="status-pill status-{{ $justification->statut }}">{{ $justification->libelleStatut() }}</span>
                </div>

                <div class="info-grid request-info-grid">
                    <div>
                        <strong>Élève</strong>
                        <p>{{ $justification->absenceRetard?->inscription?->eleve?->nom }} {{ $justification->absenceRetard?->inscription?->eleve?->prenom }}</p>
                        <small>{{ $justification->absenceRetard?->inscription?->classe?->nom ?? '-' }} / {{ $justification->absenceRetard?->inscription?->classe?->anneeScolaire?->libelle ?? '-' }}</small>
                    </div>
                    <div>
                        <strong>Parent</strong>
                        <p>{{ $justification->parent?->nom }} {{ $justification->parent?->prenom }}</p>
                        <small>{{ $justification->parent?->phone ?? $justification->parent?->email ?? '-' }}</small>
                    </div>
                    <div>
                        <strong>Événement</strong>
                        <p>{{ $justification->absenceRetard?->libelleType() }} du {{ $justification->absenceRetard?->date_debut?->format('d/m/Y') }}</p>
                        <small>{{ $justification->absenceRetard?->libellePeriode() }} — {{ $justification->absenceRetard?->libelleStatut() }}</small>
                    </div>
                    <div>
                        <strong>Date de demande</strong>
                        <p>{{ $justification->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="info-grid-full">
                        <strong>Explication du parent</strong>
                        <p>{{ $justification->message ?? 'Aucune explication complémentaire.' }}</p>
                    </div>
                    <div>
                        <strong>Pièce jointe</strong>
                        <p>
                            @if ($justification->piece_jointe)
                                <a href="{{ route('gestionnaire.justifications-parent.piece', $justification) }}" class="btn btn-sm">Ouvrir la pièce</a>
                            @else
                                Aucune pièce jointe
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="card request-detail-side">
                <h2>Traitement</h2>

                @if ($justification->estEnAttente())
                    <form action="{{ route('gestionnaire.justifications-parent.accepter', $justification) }}" method="POST" data-confirm="Accepter cette justification ?" class="decision-box decision-box-success">
                        @csrf
                        <h3>Accepter</h3>
                        <p>L’absence ou le retard sera marqué comme justifié.</p>
                        <textarea name="commentaire_traitement" class="form-control" rows="4" placeholder="Commentaire optionnel"></textarea>
                        <button type="submit" class="btn btn-success">Accepter</button>
                    </form>

                    <form action="{{ route('gestionnaire.justifications-parent.refuser', $justification) }}" method="POST" data-confirm="Refuser cette justification ?" class="decision-box decision-box-danger">
                        @csrf
                        <h3>Refuser</h3>
                        <p>Explique pourquoi la justification est refusée.</p>
                        <textarea name="commentaire_traitement" class="form-control" rows="4" placeholder="Motif du refus" required></textarea>
                        <button type="submit" class="btn btn-danger">Refuser</button>
                    </form>
                @else
                    <div class="processed-box">
                        <strong>Demande déjà traitée</strong>
                        <p>{{ $justification->commentaire_traitement ?? 'Aucun commentaire.' }}</p>
                        <small>
                            Par {{ $justification->traitePar?->nom ?? '-' }} {{ $justification->traitePar?->prenom ?? '' }}
                            @if ($justification->traite_le)
                                le {{ $justification->traite_le->format('d/m/Y H:i') }}
                            @endif
                        </small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
