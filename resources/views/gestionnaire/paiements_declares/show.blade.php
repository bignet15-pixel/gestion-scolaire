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
                <h1>Paiement déclaré</h1>
                <p>Consulte la déclaration avant de créer ou non un paiement officiel.</p>
            </div>
            <a href="{{ route('gestionnaire.paiements-declares.index') }}" class="btn">Retour à la liste</a>
        </div>

        <div class="request-detail-grid">
            <div class="card request-detail-main">
                <div class="request-detail-titlebar">
                    <h2>{{ number_format($paiementDeclare->montant, 0, ',', ' ') }} FCFA</h2>
                    <span class="status-pill status-{{ $paiementDeclare->statut }}">{{ $paiementDeclare->libelleStatut() }}</span>
                </div>

                <div class="info-grid request-info-grid">
                    <div>
                        <strong>Élève</strong>
                        <p>{{ $paiementDeclare->inscription?->eleve?->nom }} {{ $paiementDeclare->inscription?->eleve?->prenom }}</p>
                        <small>{{ $paiementDeclare->inscription?->eleve?->matricule ?? '-' }}</small>
                    </div>
                    <div>
                        <strong>Classe / année</strong>
                        <p>{{ $paiementDeclare->inscription?->classe?->nom ?? '-' }}</p>
                        <small>{{ $paiementDeclare->inscription?->anneeScolaire?->libelle ?? '-' }}</small>
                    </div>
                    <div>
                        <strong>Parent</strong>
                        <p>{{ $paiementDeclare->parent?->nom }} {{ $paiementDeclare->parent?->prenom }}</p>
                        <small>{{ $paiementDeclare->parent?->phone ?? $paiementDeclare->parent?->email ?? '-' }}</small>
                    </div>
                    <div>
                        <strong>Date de déclaration</strong>
                        <p>{{ $paiementDeclare->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <strong>Mode de paiement</strong>
                        <p>{{ str_replace('_', ' ', $paiementDeclare->mode_paiement) }}</p>
                    </div>
                    <div>
                        <strong>Numéro utilisé</strong>
                        <p>{{ $paiementDeclare->numero_transfert ?? '-' }}</p>
                    </div>
                    <div>
                        <strong>Référence</strong>
                        <p>{{ $paiementDeclare->reference_transaction ?? '-' }}</p>
                    </div>
                    <div>
                        <strong>Preuve</strong>
                        <p>
                            @if ($paiementDeclare->preuve_paiement)
                                <a href="{{ route('gestionnaire.paiements-declares.preuve', $paiementDeclare) }}" class="btn btn-sm">Ouvrir la preuve</a>
                            @else
                                Aucune preuve envoyée
                            @endif
                        </p>
                    </div>
                    <div>
                        <strong>Paiement officiel</strong>
                        <p>
                            @if ($paiementDeclare->paiement)
                                <a href="{{ route('paiements.show', $paiementDeclare->paiement) }}" class="btn btn-sm">Voir le paiement</a>
                            @else
                                Non créé
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="card request-detail-side">
                <h2>Traitement</h2>

                @if ($paiementDeclare->estEnAttente())
                    <form action="{{ route('gestionnaire.paiements-declares.valider', $paiementDeclare) }}" method="POST" data-confirm="Valider ce paiement déclaré et créer un paiement officiel ?" class="decision-box decision-box-success">
                        @csrf
                        <h3>Valider le paiement</h3>
                        <p>Un paiement officiel sera créé pour cette inscription.</p>
                        <textarea name="commentaire_validation" class="form-control" rows="4" placeholder="Commentaire optionnel"></textarea>
                        <button type="submit" class="btn btn-success">Valider</button>
                    </form>

                    <form action="{{ route('gestionnaire.paiements-declares.refuser', $paiementDeclare) }}" method="POST" data-confirm="Refuser ce paiement déclaré ?" class="decision-box decision-box-danger">
                        @csrf
                        <h3>Refuser le paiement</h3>
                        <p>Le motif du refus sera conservé dans l’historique.</p>
                        <textarea name="commentaire_validation" class="form-control" rows="4" placeholder="Motif du refus" required></textarea>
                        <button type="submit" class="btn btn-danger">Refuser</button>
                    </form>
                @else
                    <div class="processed-box">
                        <strong>Demande déjà traitée</strong>
                        <p>{{ $paiementDeclare->commentaire_validation ?? 'Aucun commentaire.' }}</p>
                        <small>
                            Par {{ $paiementDeclare->validePar?->nom ?? '-' }} {{ $paiementDeclare->validePar?->prenom ?? '' }}
                            @if ($paiementDeclare->valide_le)
                                le {{ $paiementDeclare->valide_le->format('d/m/Y H:i') }}
                            @endif
                        </small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
