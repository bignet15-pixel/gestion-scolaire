<x-app-layout>
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Espace parent</div>
                <h1>{{ $eleve->nom }} {{ $eleve->prenom }}</h1>
                <p>Fiche consultable par le parent connecté : parcours, paiements, notes visibles, absences et sanctions.</p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('dashboard') }}" class="btn">
                    Retour
                </a>
            </div>
        </div>

        <div class="student-profile-card">
            <div class="student-photo-box">
                @if ($eleve->photo)
                    <img src="{{ asset('storage/' . $eleve->photo) }}" alt="Photo élève">
                @else
                    <div class="student-photo-placeholder">
                        {{ strtoupper(substr($eleve->nom, 0, 1)) }}{{ strtoupper(substr($eleve->prenom, 0, 1)) }}
                    </div>
                @endif
            </div>

            <div class="student-info">
                <div class="student-name">
                    {{ $eleve->nom }} {{ $eleve->prenom }}
                </div>

                <div class="student-matricule">
                    Matricule : {{ $eleve->matricule }}
                </div>

                <div class="profile-grid">
                    <div class="profile-row">
                        <span>Sexe</span>
                        <strong>{{ $eleve->sexe }}</strong>
                    </div>

                    <div class="profile-row">
                        <span>Date de naissance</span>
                        <strong>{{ $eleve->date_naissance?->format('d/m/Y') ?? '-' }}</strong>
                    </div>

                    <div class="profile-row">
                        <span>Lieu de naissance</span>
                        <strong>{{ $eleve->lieu_naissance ?? '-' }}</strong>
                    </div>

                    <div class="profile-row">
                        <span>Contact parent</span>
                        <strong>{{ $eleve->contact_parent ?? '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Parcours et paiements</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Année scolaire</th>
                            <th>Classe</th>
                            <th>Frais attendus</th>
                            <th>Total payé</th>
                            <th>Reste</th>
                            <th>Statut</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($eleve->inscriptions->sortByDesc('date_inscription') as $inscription)
                            <tr>
                                <td>{{ $inscription->anneeScolaire?->libelle ?? '-' }}</td>
                                <td>{{ $inscription->classe?->nom ?? '-' }}</td>
                                <td>{{ number_format($inscription->frais_attendu, 0, ',', ' ') }} FCFA</td>
                                <td>{{ number_format($inscription->paiements->sum('montant'), 0, ',', ' ') }} FCFA</td>
                                <td>{{ number_format(max(0, (float) $inscription->frais_attendu - (float) $inscription->paiements->sum('montant')), 0, ',', ' ') }} FCFA</td>
                                <td>{{ $inscription->statut }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    Aucun parcours scolaire trouvé.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Notes enregistrées</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Année</th>
                            <th>Classe</th>
                            <th>Trimestre</th>
                            <th>Matière</th>
                            <th>Évaluation</th>
                            <th>Note</th>
                            <th>Barème</th>
                            <th>Appréciation</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            $notes = $eleve->inscriptions
                                ->flatMap(function ($inscription) {
                                    return $inscription->notes->map(function ($note) use ($inscription) {
                                        $note->setRelation('inscription_parent', $inscription);

                                        return $note;
                                    });
                                })
                                ->sortByDesc(fn ($note) => $note->evaluation?->date_evaluation);
                        @endphp

                        @forelse ($notes as $note)
                            @php
                                $inscription = $note->getRelation('inscription_parent');
                            @endphp

                            <tr>
                                <td>{{ $inscription->anneeScolaire?->libelle ?? '-' }}</td>
                                <td>{{ $inscription->classe?->nom ?? '-' }}</td>
                                <td>{{ $note->evaluation?->trimestre?->nom ?? '-' }}</td>
                                <td>{{ $note->evaluation?->matiere?->nom ?? '-' }}</td>
                                <td>{{ $note->evaluation?->nom ?? '-' }}</td>
                                <td>{{ $note->valeur }}</td>
                                <td>{{ $note->evaluation?->bareme ?? '-' }}</td>
                                <td>{{ $note->appreciation ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    Aucune note enregistrée.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Absences et retards visibles</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Classe</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Période</th>
                            <th>Motif</th>
                            <th>Statut</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($absencesRetards as $absenceRetard)
                            <tr>
                                <td>{{ $absenceRetard->inscription?->classe?->nom ?? '-' }}</td>
                                <td>{{ $absenceRetard->libelleType() }}</td>
                                <td>{{ $absenceRetard->date_debut?->format('d/m/Y') }}</td>
                                <td>{{ $absenceRetard->libellePeriode() }}</td>
                                <td>{{ $absenceRetard->motif ?? '-' }}</td>
                                <td>{{ $absenceRetard->libelleStatut() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    Aucune absence ou retard visible.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Sanctions visibles</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Classe</th>
                            <th>Sanction</th>
                            <th>Trimestre</th>
                            <th>Motif</th>
                            <th>Effet</th>
                            <th>Statut</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($sanctions as $sanctionAppliquee)
                            <tr>
                                <td>{{ $sanctionAppliquee->inscription?->classe?->nom ?? '-' }}</td>
                                <td>{{ $sanctionAppliquee->sanction?->nom ?? '-' }}</td>
                                <td>{{ $sanctionAppliquee->trimestre?->nom ?? '-' }}</td>
                                <td>{{ $sanctionAppliquee->motif ?? '-' }}</td>
                                <td>
                                    {{ $sanctionAppliquee->type_effet }}
                                    @if ($sanctionAppliquee->valeur_effet !== null)
                                        : {{ number_format($sanctionAppliquee->valeur_effet, 2, ',', ' ') }}
                                    @endif
                                </td>
                                <td>{{ $sanctionAppliquee->statut }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    Aucune sanction visible.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
