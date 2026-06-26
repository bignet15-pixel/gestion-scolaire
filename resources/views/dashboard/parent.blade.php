<x-app-layout>
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Espace parent</div>
                <h1>Tableau de bord parent</h1>
                <p>
                    Les indicateurs financiers ci-dessous concernent uniquement
                    l’année scolaire active : {{ $anneeActive?->libelle ?? 'non définie' }}.
                </p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('parent.paiements-declares.index') }}" class="btn">
                    Historique paiements déclarés
                </a>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-title">Enfants liés</div>
                <div class="stat-value">{{ $enfants->count() }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Frais attendus</div>
                <div class="stat-value">{{ number_format($totalFraisAttendus, 0, ',', ' ') }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Frais payés</div>
                <div class="stat-value">{{ number_format($totalFraisCollectes, 0, ',', ' ') }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Reste à payer</div>
                <div class="stat-value">{{ number_format($totalRestant, 0, ',', ' ') }}</div>
            </div>
        </div>

        <div class="card">
            <h2>Mes enfants</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Élève</th>
                            <th>Classe année active</th>
                            <th>Frais attendus</th>
                            <th>Payé</th>
                            <th>Reste</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($enfants as $enfant)
                            @php
                                $inscriptionActive = $enfant->inscriptions->first();
                                $paye = $inscriptionActive?->paiements?->sum('montant') ?? 0;
                                $reste = max(0, (float) ($inscriptionActive?->frais_attendu ?? 0) - (float) $paye);
                            @endphp

                            <tr>
                                <td>{{ $enfant->matricule }}</td>
                                <td>
                                    {{ $enfant->nom }} {{ $enfant->prenom }}
                                    <br>
                                    <small>{{ $enfant->pivot->lien_parente ?? '-' }}</small>
                                </td>
                                <td>
                                    {{ $inscriptionActive?->classe?->nom ?? '-' }}
                                    <br>
                                    <small>{{ $inscriptionActive?->anneeScolaire?->libelle ?? '-' }}</small>
                                </td>
                                <td>{{ number_format($inscriptionActive?->frais_attendu ?? 0, 0, ',', ' ') }} FCFA</td>
                                <td>{{ number_format($paye, 0, ',', ' ') }} FCFA</td>
                                <td>{{ number_format($reste, 0, ',', ' ') }} FCFA</td>
                                <td>
                                    <a href="{{ route('parent.eleves.show', $enfant) }}" class="btn btn-primary">
                                        Voir détail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">Aucun élève n’est lié à votre compte.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Dernières absences et retards visibles</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Classe</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Période</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($absencesRetards as $absenceRetard)
                            <tr>
                                <td>{{ $absenceRetard->inscription?->eleve?->nom }} {{ $absenceRetard->inscription?->eleve?->prenom }}</td>
                                <td>{{ $absenceRetard->inscription?->classe?->nom ?? '-' }}</td>
                                <td>{{ $absenceRetard->libelleType() }}</td>
                                <td>{{ $absenceRetard->date_debut?->format('d/m/Y') }}</td>
                                <td>{{ $absenceRetard->libellePeriode() }}</td>
                                <td>
                                    <span class="badge {{ $absenceRetard->statut === 'justifiee' ? 'badge-success' : 'badge-warning' }}">
                                        {{ $absenceRetard->libelleStatut() }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('parent.eleves.show', $absenceRetard->inscription->eleve) }}#assiduite" class="btn">
                                        Voir / justifier
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">Aucune absence ou retard visible.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Paiements déclarés récents</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Classe</th>
                            <th>Montant</th>
                            <th>Mode</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($paiementsDeclares as $paiementDeclare)
                            <tr>
                                <td>{{ $paiementDeclare->inscription?->eleve?->nom }} {{ $paiementDeclare->inscription?->eleve?->prenom }}</td>
                                <td>{{ $paiementDeclare->inscription?->classe?->nom ?? '-' }}</td>
                                <td>{{ number_format($paiementDeclare->montant, 0, ',', ' ') }} FCFA</td>
                                <td>{{ str_replace('_', ' ', $paiementDeclare->mode_paiement) }}</td>
                                <td>{{ $paiementDeclare->libelleStatut() }}</td>
                                <td>{{ $paiementDeclare->created_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">Aucun paiement déclaré récemment.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Dernières sanctions visibles</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Classe</th>
                            <th>Sanction</th>
                            <th>Trimestre</th>
                            <th>Statut</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($sanctions as $sanctionAppliquee)
                            <tr>
                                <td>{{ $sanctionAppliquee->inscription?->eleve?->nom }} {{ $sanctionAppliquee->inscription?->eleve?->prenom }}</td>
                                <td>{{ $sanctionAppliquee->inscription?->classe?->nom ?? '-' }}</td>
                                <td>{{ $sanctionAppliquee->sanction?->nom ?? '-' }}</td>
                                <td>{{ $sanctionAppliquee->trimestre?->nom ?? '-' }}</td>
                                <td>{{ $sanctionAppliquee->statut }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">Aucune sanction visible.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
