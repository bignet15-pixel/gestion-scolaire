<x-app-layout>
    <div class="container">
        <h1>Tableau de bord parent</h1>

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
                            <th>Classe actuelle</th>
                            <th>Lien</th>
                            <th>Responsable principal</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($enfants as $enfant)
                            @php
                                $derniereInscription = $enfant->inscriptions
                                    ->sortByDesc('date_inscription')
                                    ->first();
                            @endphp

                            <tr>
                                <td>{{ $enfant->matricule }}</td>
                                <td>{{ $enfant->nom }} {{ $enfant->prenom }}</td>
                                <td>
                                    {{ $derniereInscription?->classe?->nom ?? '-' }}
                                    <br>
                                    <small>{{ $derniereInscription?->anneeScolaire?->libelle ?? '-' }}</small>
                                </td>
                                <td>{{ $enfant->pivot->lien_parente ?? '-' }}</td>
                                <td>
                                    @if ($enfant->pivot->responsable_principal)
                                        <span class="badge badge-success">Oui</span>
                                    @else
                                        <span class="badge badge-muted">Non</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('parent.eleves.show', $enfant) }}" class="btn btn-primary">
                                        Voir détail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    Aucun élève n’est lié à votre compte.
                                </td>
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
                                <td colspan="5">
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
