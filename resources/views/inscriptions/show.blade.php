<x-app-layout>
{{-- Vue Blade : resources/views/inscriptions/show.blade.php --}}
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Détail inscription</div>

                <h1>
                    {{ $inscription->eleve?->nom }}
                    {{ $inscription->eleve?->prenom }}
                </h1>

                <p>
                    Inscription de l’élève dans une classe pour une année scolaire,
                    avec suivi des frais, paiements et notes.
                </p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('inscriptions.index') }}" class="btn">
                    Retour
                </a>

                <a href="{{ route('eleves.show', $inscription->eleve) }}" class="btn btn-success">
                    Voir fiche élève
                </a>

                {{-- Condition : ! $inscription->anneeScolaire?->estFermee(). --}}
                @if (! $inscription->anneeScolaire?->estFermee())
                    <a href="{{ route('inscriptions.edit', $inscription) }}" class="btn btn-primary">
                        Modifier
                    </a>
                @endif
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-info-card">
                <div class="detail-label">Matricule</div>
                <div class="detail-value">
                    {{ $inscription->eleve?->matricule ?? '-' }}
                </div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Classe</div>
                <div class="detail-value">
                    {{ $inscription->classe?->nom ?? '-' }}
                </div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Année scolaire</div>
                <div class="detail-value">
                    {{ $inscription->anneeScolaire?->libelle ?? '-' }}
                </div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Statut</div>
                <div class="detail-value">
                    {{-- Condition : $inscription->statut === 'actif'. --}}
                    @if ($inscription->statut === 'actif')
                        <span class="badge badge-success">Actif</span>
                    {{-- Sinon, autre cas prevu par la vue. --}}
                    @elseif ($inscription->statut === 'termine')
                        <span class="badge">Terminé</span>
                    {{-- Sinon, autre cas prevu par la vue. --}}
                    @elseif ($inscription->statut === 'abandonne')
                        <span class="badge badge-danger">Abandonné</span>
                    {{-- Sinon, affichage de l alternative prevue. --}}
                    @else
                        <span class="badge badge-warning">{{ $inscription->statut }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="student-profile-card">
            <div class="student-photo-box">
                {{-- Condition : $inscription->eleve?->photo. --}}
                @if ($inscription->eleve?->photo)
                    <img src="{{ asset('storage/' . $inscription->eleve->photo) }}" alt="Photo élève">
                {{-- Sinon, affichage de l alternative prevue. --}}
                @else
                    <div class="student-photo-placeholder">
                        {{ strtoupper(substr($inscription->eleve?->nom ?? 'E', 0, 1)) }}{{ strtoupper(substr($inscription->eleve?->prenom ?? 'L', 0, 1)) }}
                    </div>
                @endif
            </div>

            <div class="student-info">
                <div class="student-name">
                    {{ $inscription->eleve?->nom }}
                    {{ $inscription->eleve?->prenom }}
                </div>

                <div class="student-matricule">
                    Matricule : {{ $inscription->eleve?->matricule ?? '-' }}
                </div>

                <div class="profile-grid">
                    <div class="profile-row">
                        <span>Sexe</span>
                        <strong>{{ $inscription->eleve?->sexe ?? '-' }}</strong>
                    </div>

                    <div class="profile-row">
                        <span>Contact parent</span>
                        <strong>{{ $inscription->eleve?->contact_parent ?? '-' }}</strong>
                    </div>

                    <div class="profile-row">
                        <span>Date inscription</span>
                        <strong>{{ $inscription->date_inscription?->format('d/m/Y') ?? '-' }}</strong>
                    </div>

                    <div class="profile-row">
                        <span>Lieu naissance</span>
                        <strong>{{ $inscription->eleve?->lieu_naissance ?? '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="card inscription-finance-card">
            <h2>Situation financière de l’inscription</h2>

            <div class="finance-grid">
                <div class="finance-item">
                    <div class="finance-label">Frais attendus</div>
                    <div class="finance-value">
                        {{ number_format($inscription->frais_attendu, 0, ',', ' ') }} FCFA
                    </div>
                </div>

                <div class="finance-item success">
                    <div class="finance-label">Total payé</div>
                    <div class="finance-value">
                        {{ number_format($inscription->totalPaye(), 0, ',', ' ') }} FCFA
                    </div>
                </div>

                <div class="finance-item danger">
                    <div class="finance-label">Reste à payer</div>
                    <div class="finance-value">
                        {{ number_format($inscription->resteAPayer(), 0, ',', ' ') }} FCFA
                    </div>
                </div>

                <div class="finance-item warning">
                    <div class="finance-label">Paiements</div>
                    <div class="finance-value">
                        {{ $inscription->paiements->count() }}
                    </div>
                </div>
            </div>

            <div class="finance-actions">
                <a href="{{ route('paiements.create', ['inscription_id' => $inscription->id]) }}" class="btn btn-primary">
                    Ajouter un paiement
                </a>

                <a href="{{ route('paiements.index') }}" class="btn">
                    Voir les paiements
                </a>
            </div>
        </div>

        <div class="card">
            <h2>Paiements enregistrés</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Mode</th>
                        <th>Contact parent</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche l historique des paiements, ou le message vide si aucun paiement n existe. --}}
                    @forelse ($inscription->paiements as $paiement)
                        <tr>
                            <td>{{ $paiement->numero_paiement }}</td>
                            <td>{{ $paiement->date_paiement?->format('d/m/Y') ?? '-' }}</td>
                            <td>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                            <td>{{ $paiement->mode_paiement }}</td>
                            <td>{{ $paiement->contact_parent ?? '-' }}</td>
                            <td>
                                <a href="{{ route('paiements.show', $paiement) }}" class="btn btn-success">
                                    Détail
                                </a>

                                <a href="{{ route('paiements.recu', $paiement) }}" class="btn btn-primary">
                                    Reçu PDF
                                </a>
                            </td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="6">
                                Aucun paiement enregistré pour cette inscription.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Notes liées à cette inscription</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Trimestre</th>
                        <th>Évaluation</th>
                        <th>Matière</th>
                        <th>Type</th>
                        <th>Note</th>
                        <th>Barème</th>
                        <th>Appréciation</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les notes de l evaluation, ou le message vide si aucune note n existe. --}}
                    @forelse ($inscription->notes as $note)
                        <tr>
                            <td>{{ $note->evaluation?->date_evaluation?->format('d/m/Y') ?? '-' }}</td>
                            <td>{{ $note->evaluation?->trimestre?->nom ?? '-' }}</td>
                            <td>{{ $note->evaluation?->nom ?? '-' }}</td>
                            <td>{{ $note->evaluation?->matiere?->nom ?? '-' }}</td>
                            <td>{{ $note->evaluation?->type ?? '-' }}</td>
                            <td>{{ $note->valeur }}</td>
                            <td>{{ $note->evaluation?->bareme ?? '-' }}</td>
                            <td>{{ $note->appreciation ?? '-' }}</td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="8">
                                Aucune note enregistrée pour cette inscription.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
