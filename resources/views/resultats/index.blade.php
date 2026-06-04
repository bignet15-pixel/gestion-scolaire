<x-app-layout>
{{-- Vue Blade : resources/views/resultats/index.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Moyennes et classements</h1>

            <form action="{{ route('resultats.index') }}" method="GET" class="filter-form filter-form-large">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>

                    <select name="annee_scolaire_id" class="form-control">
                        {{-- Remplit la liste des annees scolaires. --}}
                        @foreach ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected((string) $selectedAnneeId === (string) $annee->id)>
                                {{ $annee->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Classe</label>

                    <select name="classe_id" class="form-control">
                        {{-- Remplit la liste des classes disponibles. --}}
                        @foreach ($classes as $classeOption)
                            <option value="{{ $classeOption->id }}" @selected((string) $selectedClasseId === (string) $classeOption->id)>
                                {{ $classeOption->nom }} — {{ $classeOption->anneeScolaire->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Période</label>

                    <select name="periode" class="form-control">
                        {{-- Remplit la liste des trimestres disponibles. --}}
                        @foreach ($trimestres as $trimestreOption)
                            <option value="{{ $trimestreOption->id }}" @selected((string) $selectedPeriode === (string) $trimestreOption->id)>
                                {{ $trimestreOption->nom }}
                            </option>
                        @endforeach

                        <option value="annuel" @selected($selectedPeriode === 'annuel')>
                            Annuel
                        </option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Afficher
                    </button>
                </div>
            </form>
        </div>

        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-title">Moyenne minimale</div>
                <div class="stat-value">
                    {{-- Condition : $stats['moyenne_min'] !== null. --}}
                    @if ($stats['moyenne_min'] !== null)
                        {{ number_format($stats['moyenne_min'], 2, ',', ' ') }}/20
                    {{-- Sinon, affichage de l alternative prevue. --}}
                    @else
                        -
                    @endif
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Moyenne maximale</div>
                <div class="stat-value">
                    {{-- Condition : $stats['moyenne_max'] !== null. --}}
                    @if ($stats['moyenne_max'] !== null)
                        {{ number_format($stats['moyenne_max'], 2, ',', ' ') }}/20
                    {{-- Sinon, affichage de l alternative prevue. --}}
                    @else
                        -
                    @endif
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Moyenne classe</div>
                <div class="stat-value">
                    {{-- Condition : $stats['moyenne_classe'] !== null. --}}
                    @if ($stats['moyenne_classe'] !== null)
                        {{ number_format($stats['moyenne_classe'], 2, ',', ' ') }}/20
                    {{-- Sinon, affichage de l alternative prevue. --}}
                    @else
                        -
                    @endif
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Élèves validés</div>
                <div class="stat-value">
                    {{ $stats['nombre_valides'] }}
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Pourcentage validation</div>
                <div class="stat-value">
                    {{ number_format($stats['pourcentage_validation'], 2, ',', ' ') }}%
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Élèves avec moyenne</div>
                <div class="stat-value">
                    {{ $stats['nombre_avec_moyenne'] }}/{{ $stats['nombre_valides'] }}
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Total élèves</div>
                <div class="stat-value">
                    {{ $stats['nombre_total'] }}
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Élèves sans moyenne</div>
                <div class="stat-value">
                    {{ $stats['nombre_sans_moyenne'] }}/{{ $stats['nombre_valides'] }}
                </div>
            </div>
        </div>

        {{-- Condition : $selectedPeriode === 'annuel'. --}}
        @if ($selectedPeriode === 'annuel')
            <div class="card">
                <h2>
                    Résultats annuels —
                    {{ $classe?->nom ?? '-' }}
                    —
                    {{ $classe?->anneeScolaire?->libelle ?? '-' }}
                </h2>

                <p class="text-muted">
                    La décision annuelle est calculée uniquement si les trois trimestres possèdent une moyenne.
                    Seuil de passage : 10/20.
                </p>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Rang annuel</th>
                            <th>Matricule</th>
                            <th>Nom</th>
                            <th>Prénom</th>

                            {{-- Remplit la liste des trimestres disponibles. --}}
                            @foreach ($trimestres as $trimestreOption)
                                <th>{{ $trimestreOption->nom }}</th>
                            @endforeach

                            <th>Moyenne annuelle</th>
                            <th>Décision</th>
                        </tr>
                    </thead>

                    <tbody>
                        {{-- Affiche les resultats annuels des eleves, ou le message vide si aucun resultat n existe. --}}
                        @forelse ($resultatsAnnuels as $resultat)
                            <tr>
                                <td>{{ $resultat['rang_annuel'] ?? '-' }}</td>
                                <td>{{ $resultat['inscription']->eleve?->matricule }}</td>
                                <td>{{ $resultat['inscription']->eleve?->nom }}</td>
                                <td>{{ $resultat['inscription']->eleve?->prenom }}</td>

                                {{-- Remplit la liste des trimestres disponibles. --}}
                                @foreach ($trimestres as $trimestreOption)
                                    {{-- Preparation des donnees de la vue. --}}
                                    @php
                                        $moyenneTrimestre = $resultat['moyennes_trimestres'][$trimestreOption->id] ?? null;
                                    @endphp

                                    <td>
                                        {{-- Condition : $moyenneTrimestre !== null. --}}
                                        @if ($moyenneTrimestre !== null)
                                            {{ number_format($moyenneTrimestre, 2, ',', ' ') }}/20
                                        {{-- Sinon, affichage de l alternative prevue. --}}
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endforeach

                                <td>
                                    {{-- Condition : $resultat['moyenne_annuelle'] !== null. --}}
                                    @if ($resultat['moyenne_annuelle'] !== null)
                                        <strong>
                                            {{ number_format($resultat['moyenne_annuelle'], 2, ',', ' ') }}/20
                                        </strong>
                                    {{-- Sinon, affichage de l alternative prevue. --}}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    {{-- Condition : $resultat['decision'] === 'Passe'. --}}
                                    @if ($resultat['decision'] === 'Passe')
                                        <span class="badge badge-success">Passe</span>
                                    {{-- Sinon, autre cas prevu par la vue. --}}
                                    @elseif ($resultat['decision'] === 'Redouble')
                                        <span class="badge badge-danger">Redouble</span>
                                    {{-- Sinon, affichage de l alternative prevue. --}}
                                    @else
                                        <span class="badge badge-warning">Résultat incomplet</span>
                                    @endif
                                </td>
                            </tr>
                        {{-- Message affiche quand la liste est vide. --}}
                        @empty
                            <tr>
                                <td colspan="{{ 6 + $trimestres->count() }}">
                                    Aucun résultat annuel trouvé.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        {{-- Sinon, affichage de l alternative prevue. --}}
        @else
            <div class="card">
                <h2>
                    {{ $classe?->nom ?? '-' }}
                    —
                    {{ $classe?->anneeScolaire?->libelle ?? '-' }}
                    —
                    {{ $trimestre?->nom ?? '-' }}
                </h2>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Rang</th>
                            <th>Matricule</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Nombre de notes</th>
                            <th>Moyenne / 20</th>
                            <th>Appréciation</th>
                        </tr>
                    </thead>

                    <tbody>
                        {{-- Affiche le classement des eleves, ou le message vide si aucun resultat n existe. --}}
                        @forelse ($classement as $resultat)
                            <tr>
                                <td>{{ $resultat['rang'] ?? '-' }}</td>
                                <td>{{ $resultat['inscription']->eleve?->matricule }}</td>
                                <td>{{ $resultat['inscription']->eleve?->nom }}</td>
                                <td>{{ $resultat['inscription']->eleve?->prenom }}</td>
                                <td>{{ $resultat['nombre_notes'] }}</td>

                                <td>
                                    {{-- Condition : $resultat['moyenne'] !== null. --}}
                                    @if ($resultat['moyenne'] !== null)
                                        {{ number_format($resultat['moyenne'], 2, ',', ' ') }}/20
                                    {{-- Sinon, affichage de l alternative prevue. --}}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>{{ $resultat['appreciation'] }}</td>
                            </tr>
                        {{-- Message affiche quand la liste est vide. --}}
                        @empty
                            <tr>
                                <td colspan="7">
                                    Aucun résultat trouvé.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
