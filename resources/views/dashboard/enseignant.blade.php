<x-app-layout>
{{-- Vue Blade : resources/views/dashboard/enseignant.blade.php --}}
    <div class="container">
        <h1>Tableau de bord enseignant</h1>

        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-title">Classes affectées</div>
                <div class="stat-value">{{ $nombreClasses }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Matières affectées</div>
                <div class="stat-value">{{ $nombreMatieres }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Élèves concernés</div>
                <div class="stat-value">{{ $nombreEleves }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Évaluations</div>
                <div class="stat-value">{{ $nombreEvaluations }}</div>
            </div>
        </div>

        <div class="card">
            <h2>Accès rapides</h2>

            <p>
                <a href="{{ route('evaluations.index') }}" class="btn btn-primary">
                    Mes évaluations
                </a>

                <a href="{{ route('emplois-du-temps.semaine-enseignant') }}" class="btn btn-primary">
                    Mon emploi du temps
                </a>

                <a href="{{ route('resultats.index') }}" class="btn btn-success">
                    Moyennes / Classements
                </a>
            </p>
        </div>

        <div class="card">
            <h2>Mes affectations</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Année</th>
                        <th>Classe</th>
                        <th>Matière</th>
                        <th>Début</th>
                        <th>Statut</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les affectations dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($affectations as $affectation)
                        <tr>
                            <td>{{ $affectation->classe->anneeScolaire->libelle }}</td>
                            <td>{{ $affectation->classe->nom }}</td>
                            <td>{{ $affectation->matiere->nom }}</td>
                            <td>{{ $affectation->date_debut?->format('d/m/Y') }}</td>
                            <td>{{ $affectation->statut }}</td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="5">Aucune affectation active.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Mon emploi du temps</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Jour</th>
                        <th>Heure</th>
                        <th>Classe</th>
                        <th>Matière</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les cours du tableau de bord enseignant, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($emploisDuTemps as $emploi)
                        <tr>
                            <td>{{ ucfirst($emploi->jour) }}</td>
                            <td>
                                {{ $emploi->heure_debut->format('H:i') }}
                                -
                                {{ $emploi->heure_fin->format('H:i') }}
                            </td>
                            <td>{{ $emploi->affectation->classe->nom }}</td>
                            <td>{{ $emploi->affectation->matiere->nom }}</td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="4">Aucun créneau trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Mes dernières évaluations</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Classe</th>
                        <th>Matière</th>
                        <th>Nom</th>
                        <th>Type</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les evaluations recentes du tableau de bord, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($evaluationsRecentes as $evaluation)
                        <tr>
                            <td>{{ $evaluation->date_evaluation?->format('d/m/Y') }}</td>
                            <td>{{ $evaluation->classe->nom }}</td>
                            <td>{{ $evaluation->matiere->nom }}</td>
                            <td>{{ $evaluation->nom }}</td>
                            <td>{{ $evaluation->type }}</td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="5">Aucune évaluation trouvée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>