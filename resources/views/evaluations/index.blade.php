<x-app-layout>
{{-- Vue Blade : resources/views/evaluations/index.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Évaluations</h1>

            {{-- Une évaluation ne se crée que dans une année et un trimestre encore ouverts. --}}
            {{-- Preparation des donnees de la vue. --}}
            @php
                $selectedAnnee = $selectedAnneeId
                    ? $annees->first(fn ($annee) => (string) $annee->id === (string) $selectedAnneeId)
                    : null;

                $selectedClasse = $selectedClasseId
                    ? $classes->first(fn ($classe) => (string) $classe->id === (string) $selectedClasseId)
                    : null;

                $selectedTrimestre = $selectedTrimestreId
                    ? $trimestres->first(fn ($trimestre) => (string) $trimestre->id === (string) $selectedTrimestreId)
                    : null;

                $creationVerrouillee = ($selectedAnnee?->estFermee() ?? false)
                    || ($selectedClasse?->anneeScolaire?->estFermee() ?? false)
                    || ($selectedTrimestre?->estFerme() ?? false)
                    || ($selectedTrimestre?->anneeScolaire?->estFermee() ?? false);
            @endphp

            <form action="{{ route('evaluations.index') }}" method="GET" class="filter-form filter-form-large">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>

                    <select name="annee_scolaire_id" class="form-control">
                        <option value="">Toutes les années</option>

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
                        <option value="">Toutes les classes</option>

                        {{-- Remplit la liste des classes disponibles. --}}
                        @foreach ($classes as $classe)
                            <option value="{{ $classe->id }}" @selected((string) $selectedClasseId === (string) $classe->id)>
                                {{ $classe->nom }} — {{ $classe->anneeScolaire->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Trimestre</label>

                    <select name="trimestre_id" class="form-control">
                        <option value="">Tous les trimestres</option>

                        {{-- Remplit la liste des trimestres disponibles. --}}
                        @foreach ($trimestres as $trimestre)
                            <option value="{{ $trimestre->id }}" @selected((string) $selectedTrimestreId === (string) $trimestre->id)>
                                {{ $trimestre->nom }} — {{ $trimestre->anneeScolaire?->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Filtrer
                    </button>

                    <a href="{{ route('evaluations.index') }}" class="btn">
                        Réinitialiser
                    </a>
                </div>
            </form>

            {{-- Condition : ! $creationVerrouillee. --}}
            @if (! $creationVerrouillee)
                <p>
                    <a href="{{ route('evaluations.create') }}" class="btn btn-primary">
                        Ajouter une évaluation
                    </a>
                </p>
            @endif

            {{-- Condition : session('success'). --}}
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Condition : $errors->any(). --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    {{-- Affiche les messages d erreur de validation. --}}
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Classe</th>
                        <th>Matière</th>
                        <th>Trimestre</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les evaluations dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($evaluations as $evaluation)
                        {{-- Preparation des donnees de la vue. --}}
                        @php
                            $verrouille = $evaluation->trimestre?->estFerme()
                                || $evaluation->classe?->anneeScolaire?->estFermee()
                                || $evaluation->trimestre?->anneeScolaire?->estFermee();

                            $numeroTrimestre = $evaluation->trimestre?->nom
                                ? preg_replace('/\D+/', '', $evaluation->trimestre->nom)
                                : null;
                        @endphp

                        <tr>
                            <td>{{ $evaluation->date_evaluation?->format('d/m/Y') }}</td>

                            <td>
                                {{ $evaluation->heure_debut?->format('H:i') }}
                                -
                                {{ $evaluation->heure_fin?->format('H:i') }}
                            </td>

                            <td>{{ $evaluation->nom }}</td>

                            <td>
                                <span class="badge">
                                    {{ ucfirst($evaluation->type) }}
                                </span>
                            </td>

                            <td>{{ $evaluation->classe?->nom ?? '-' }}</td>
                            <td>{{ $evaluation->matiere?->nom ?? '-' }}</td>
                            <td>{{ $numeroTrimestre ?: '-' }}</td>

                            <td>
                                <a href="{{ route('evaluations.show', $evaluation) }}" class="btn btn-success">
                                    Détail
                                </a>

                                {{-- Condition : ! $verrouille. --}}
                                @if (! $verrouille)
                                    <a href="{{ route('notes.saisie', $evaluation) }}" class="btn btn-primary">
                                        Saisir notes
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

                                    {{-- Condition : auth()->user()->estGestionnaire() || auth()->id() === $evaluation->user_id. --}}
                                    @if (auth()->user()->estGestionnaire() || auth()->id() === $evaluation->user_id)
                                        <form
                                            action="{{ route('evaluations.destroy', $evaluation) }}"
                                            method="POST"
                                            style="display:inline;"
                                            data-confirm="Voulez-vous vraiment supprimer cette évaluation ?"
                                            data-confirm-title="Suppression d’une évaluation"
                                            data-confirm-button="Supprimer"
                                        >
                                            {{-- Jeton de securite du formulaire. --}}
                                            @csrf
                                            {{-- Methode HTTP du formulaire. --}}
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-danger">
                                                Supprimer
                                            </button>
                                        </form>
                                    @endif
                                {{-- Sinon, affichage de l alternative prevue. --}}
                                @else
                                    <span class="badge">Historique</span>
                                @endif
                            </td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="8">
                                Aucune évaluation trouvée pour ce filtre.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
