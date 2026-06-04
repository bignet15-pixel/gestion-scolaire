<x-app-layout>
{{--resources/views/evaluations/show.blade.php --}}
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Détail évaluation</div>

                <h1>{{ $evaluation->nom }}</h1>

                <p>
                    Évaluation de {{ $evaluation->matiere?->nom ?? '-' }}
                    pour la classe {{ $evaluation->classe?->nom ?? '-' }}.
                </p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('evaluations.index') }}" class="btn">
                    Retour
                </a>

                {{-- Les notes restent consultables quand l'évaluation passe en historique. --}}
                {{-- Preparation des donnees de la vue. --}}
                @php
                    $verrouille = $evaluation->trimestre?->estFerme()
                        || $evaluation->classe?->anneeScolaire?->estFermee()
                        || $evaluation->trimestre?->anneeScolaire?->estFermee();
                @endphp

                {{-- Condition : ! $verrouille. --}}
                @if (! $verrouille)
                    <a href="{{ route('notes.saisie', $evaluation) }}" class="btn btn-success">
                        Saisir / modifier les notes
                    </a>

                    {{-- Condition : verification des criteres avant affichage. --}}
                    @if (
                        auth()->user()->estGestionnaire()
                        || (
                            auth()->user()->estEnseignant()
                            && auth()->id() === $evaluation->user_id
                            && in_array($evaluation->type, ['devoir', 'interrogation'])
                        )
                    )
                        <a href="{{ route('evaluations.edit', $evaluation) }}" class="btn btn-primary">
                            Modifier
                        </a>
                    @endif
                @endif
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-info-card">
                <div class="detail-label">Classe</div>
                <div class="detail-value">
                    {{ $evaluation->classe?->nom ?? '-' }}
                </div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Matière</div>
                <div class="detail-value">
                    {{ $evaluation->matiere?->nom ?? '-' }}
                </div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Trimestre</div>
                <div class="detail-value">
                    {{ $evaluation->trimestre?->nom ?? '-' }}
                </div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Type</div>
                <div class="detail-value">
                    {{ ucfirst($evaluation->type) }}
                </div>
            </div>
        </div>

        <div class="card evaluation-summary-card">
            <h2>Informations de l’évaluation</h2>

            <div class="profile-grid">
                <div class="profile-row">
                    <span>Année scolaire</span>
                    <strong>{{ $evaluation->classe?->anneeScolaire?->libelle ?? '-' }}</strong>
                </div>

                <div class="profile-row">
                    <span>Date</span>
                    <strong>{{ $evaluation->date_evaluation?->format('d/m/Y') ?? '-' }}</strong>
                </div>

                <div class="profile-row">
                    <span>Heure</span>
                    <strong>
                        {{ $evaluation->heure_debut?->format('H:i') ?? '-' }}
                        -
                        {{ $evaluation->heure_fin?->format('H:i') ?? '-' }}
                    </strong>
                </div>

                <div class="profile-row">
                    <span>Barème</span>
                    <strong>{{ $evaluation->bareme }}</strong>
                </div>

                <div class="profile-row">
                    <span>Coefficient</span>
                    <strong>{{ $evaluation->coefficient }}</strong>
                </div>

                <div class="profile-row">
                    <span>Élèves concernés</span>
                    <strong>{{ $nombreElevesConcernes }}</strong>
                </div>

                <div class="profile-row">
                    <span>Notes saisies</span>
                    <strong>{{ $nombreNotesSaisies }}</strong>
                </div>

                <div class="profile-row">
                    <span>Progression</span>
                    <strong>
                        {{-- Condition : $nombreElevesConcernes > 0. --}}
                        @if ($nombreElevesConcernes > 0)
                            {{ number_format(($nombreNotesSaisies / $nombreElevesConcernes) * 100, 2, ',', ' ') }}%
                        {{-- Sinon, affichage de l alternative prevue. --}}
                        @else
                            0%
                        @endif
                    </strong>
                </div>
            </div>

            <div class="evaluation-progress">
                <div class="evaluation-progress-head">
                    <span>Avancement de la saisie</span>

                    <strong>
                        {{ $nombreNotesSaisies }} / {{ $nombreElevesConcernes }}
                    </strong>
                </div>

                <div class="finance-progress-bar">
                    <div
                        class="finance-progress-fill"
                        style="width: {{ $nombreElevesConcernes > 0 ? min(($nombreNotesSaisies / $nombreElevesConcernes) * 100, 100) : 0 }}%;"
                    ></div>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-title">Élèves concernés</div>
                <div class="stat-value">{{ $nombreElevesConcernes }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Notes saisies</div>
                <div class="stat-value">{{ $nombreNotesSaisies }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Avec moyenne</div>
                <div class="stat-value">{{ $nombreAvecMoyenne }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Sans moyenne</div>
                <div class="stat-value">{{ $nombreSansMoyenne }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Moyenne</div>
                <div class="stat-value">
                    {{-- Condition : $moyenneEvaluation !== null. --}}
                    @if ($moyenneEvaluation !== null)
                        {{ number_format($moyenneEvaluation, 2, ',', ' ') }}/{{ $evaluation->bareme }}
                    {{-- Sinon, affichage de l alternative prevue. --}}
                    @else
                        -
                    @endif
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Pourcentage moyen</div>
                <div class="stat-value">
                    {{-- Condition : $pourcentageMoyen !== null. --}}
                    @if ($pourcentageMoyen !== null)
                        {{ number_format($pourcentageMoyen, 2, ',', ' ') }}%
                    {{-- Sinon, affichage de l alternative prevue. --}}
                    @else
                        -
                    @endif
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Note maximale</div>
                <div class="stat-value">
                    {{ $noteMax !== null ? $noteMax : '-' }}
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Note minimale</div>
                <div class="stat-value">
                    {{ $noteMin !== null ? $noteMin : '-' }}
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Notes enregistrées</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Matricule</th>
                        <th>Élève</th>
                        <th>Note</th>
                        <th>Barème</th>
                        <th>Pourcentage</th>
                        <th>Appréciation</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les notes de l evaluation, ou le message vide si aucune note n existe. --}}
                    @forelse ($evaluation->notes as $note)
                        <tr>
                            <td>{{ $note->inscription?->eleve?->matricule ?? '-' }}</td>

                            <td>
                                {{ $note->inscription?->eleve?->nom ?? '-' }}
                                {{ $note->inscription?->eleve?->prenom ?? '' }}
                            </td>

                            <td>{{ $note->valeur }}</td>

                            <td>{{ $evaluation->bareme }}</td>

                            <td>
                                {{-- Condition : $evaluation->bareme > 0. --}}
                                @if ($evaluation->bareme > 0)
                                    {{ number_format(($note->valeur / $evaluation->bareme) * 100, 2, ',', ' ') }}%
                                {{-- Sinon, affichage de l alternative prevue. --}}
                                @else
                                    -
                                @endif
                            </td>

                            <td>{{ $note->appreciation ?? '-' }}</td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="6">
                                Aucune note enregistrée pour cette évaluation.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
