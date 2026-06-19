<x-app-layout>
{{-- Vue Blade : resources/views/evaluations/create.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Ajouter une évaluation</h1>

            <form action="{{ route('evaluations.create') }}" method="GET" class="filter-form filter-form-large">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        @foreach ($annees as $anneeOption)
                            <option value="{{ $anneeOption->id }}" @selected((string) $selectedAnneeId === (string) $anneeOption->id)>
                                {{ $anneeOption->libelle }}{{ $anneeOption->estFermee() ? ' — fermée' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Classe</label>
                    <select name="classe_id" class="form-control">
                        @forelse ($classes as $classeOption)
                            <option value="{{ $classeOption->id }}" @selected((string) $selectedClasseId === (string) $classeOption->id)>
                                {{ $classeOption->nom }} — {{ $classeOption->anneeScolaire?->libelle }}
                            </option>
                        @empty
                            <option value="">Aucune classe disponible</option>
                        @endforelse
                    </select>
                </div>

                <input type="hidden" name="trimestre_id" value="{{ $selectedTrimestreId }}">

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Afficher
                    </button>
                </div>
            </form>

            @if (! $annee)
                <div class="alert alert-warning">
                    Aucune année scolaire n’est disponible pour créer une évaluation.
                </div>
            @elseif ($annee->estFermee())
                <div class="alert alert-warning">
                    Cette année scolaire est fermée. Ses évaluations sont disponibles uniquement dans l’historique.
                </div>
            @elseif ($classes->isEmpty())
                <div class="alert alert-warning">
                    Aucune classe n’est disponible pour cette année scolaire.
                </div>
            @endif

            {{-- Condition : $errors->any(). --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        {{-- Affiche les messages d erreur de validation. --}}
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('evaluations.store') }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf

                <input type="hidden" name="annee_scolaire_id" value="{{ $selectedAnneeId }}">

                {{-- Condition : auth()->user()->estEnseignant(). --}}
                @if (auth()->user()->estEnseignant())
                    {{-- Condition : $affectations->isEmpty(). --}}
                    @if ($affectations->isEmpty())
                        <div class="alert alert-warning">
                            Aucune matière ne vous est affectée dans cette classe pour l’année sélectionnée.
                        </div>
                    {{-- Sinon, affiche les affectations disponibles pour creer une evaluation. --}}
                    @else
                        <div class="form-group">
                            <label class="form-label">Classe / matière</label>
                            <select name="affectation_id" class="form-control">
                                {{-- Remplit les couples classe et matiere affectes a l enseignant pour l annee courante. --}}
                                @foreach ($affectations as $affectation)
                                    <option value="{{ $affectation->id }}" @selected(old('affectation_id') == $affectation->id)>
                                        {{ $affectation->classe?->nom }}
                                        —
                                        {{ $affectation->matiere?->nom }}
                                        —
                                        coefficient {{ number_format($affectation->coefficient, 2, ',', ' ') }}
                                        —
                                        {{ $affectation->classe?->anneeScolaire?->libelle }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                {{-- Sinon, affichage des champs administratifs. --}}
                @else
                    <div class="form-group">
                        <label class="form-label">Classe</label>
                        <select class="form-control" disabled>
                            @if ($selectedClasse)
                                <option>{{ $selectedClasse->nom }} — {{ $selectedClasse->anneeScolaire?->libelle }}</option>
                            @else
                                <option>Aucune classe disponible</option>
                            @endif
                        </select>

                        <input type="hidden" name="classe_id" value="{{ $selectedClasseId }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Matière</label>
                        <select name="matiere_id" class="form-control" @disabled($matieres->isEmpty())>
                            {{-- Affiche uniquement les matières affectées à la classe choisie. --}}
                            @forelse ($matieres as $matiere)
                                <option value="{{ $matiere->id }}" @selected(old('matiere_id') == $matiere->id)>
                                    {{ $matiere->nom }}
                                </option>
                            @empty
                                <option value="">Aucune matière affectée à cette classe</option>
                            @endforelse
                        </select>
                    </div>

                    @if ($matieres->isEmpty() && $selectedClasseId)
                        <div class="alert alert-warning">
                            Aucune matière n’a encore une affectation active dans cette classe.
                        </div>
                    @endif
                @endif

                <div class="form-group">
                    <label class="form-label">Trimestre</label>
                    <select name="trimestre_id" class="form-control" @disabled($trimestres->isEmpty())>
                        {{-- Remplit la liste des trimestres disponibles. --}}
                        @foreach ($trimestres as $trimestre)
                            <option value="{{ $trimestre->id }}" @selected((string) old('trimestre_id', $selectedTrimestreId) === (string) $trimestre->id)>
                                {{ $trimestre->nom }} — {{ $trimestre->anneeScolaire->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Condition : $trimestres->isEmpty(). --}}
                @if ($trimestres->isEmpty())
                    <div class="alert alert-warning">
                        Aucun trimestre ouvert n’est disponible pour créer une évaluation.
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Nom</label>
                    <input type="text" name="nom" class="form-control" value="{{ old('nom') }}" placeholder="Ex: Devoir 1">
                </div>

                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control">
                        {{-- Remplit la liste des types d evaluation. --}}
                        @foreach ($types as $type)
                            <option value="{{ $type }}" @selected(old('type') === $type)>
                                {{ ucfirst($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Date évaluation</label>
                    <input type="date" name="date_evaluation" class="form-control" value="{{ old('date_evaluation') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Heure début</label>
                    <input type="time" name="heure_debut" class="form-control" value="{{ old('heure_debut') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Heure fin</label>
                    <input type="time" name="heure_fin" class="form-control" value="{{ old('heure_fin') }}">
                </div>

                {{-- Condition : auth()->user()->estGestionnaire(). --}}
                @if (auth()->user()->estGestionnaire())
                    <div class="form-group">
                        <label class="form-label">Coefficient</label>
                        <input type="number" step="0.1" name="coefficient" class="form-control" value="{{ old('coefficient', 1) }}">
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Barème</label>
                    <input type="number" step="0.1" name="bareme" class="form-control" value="{{ old('bareme', 20) }}">
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                    @disabled(
                        ! $annee
                        || $annee->estFermee()
                        || $classes->isEmpty()
                        || (auth()->user()->estEnseignant() && $affectations->isEmpty())
                        || (auth()->user()->estGestionnaire() && $matieres->isEmpty())
                        || $trimestres->isEmpty()
                    )
                >
                    Enregistrer
                </button>

                <a
                    href="{{ route('evaluations.index', [
                        'annee_scolaire_id' => $selectedAnneeId,
                        'classe_id' => $selectedClasseId,
                        'trimestre_id' => $selectedTrimestreId,
                    ]) }}"
                    class="btn"
                >
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>
