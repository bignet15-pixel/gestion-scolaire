<x-app-layout>
{{-- Vue Blade : resources/views/affectations/index.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Affectations enseignant / classe / matière</h1>

            {{-- Les affectations liées à une année fermée sont affichées comme historique uniquement. --}}
            {{-- Preparation des donnees de la vue. --}}
            @php
                $selectedAnnee = $selectedAnneeId
                    ? $annees->first(fn ($annee) => (string) $annee->id === (string) $selectedAnneeId)
                    : null;

                $selectedClasse = $selectedClasseId
                    ? $classes->first(fn ($classe) => (string) $classe->id === (string) $selectedClasseId)
                    : null;

                $creationVerrouillee = ($selectedAnnee?->estFermee() ?? false)
                    || ($selectedClasse?->anneeScolaire?->estFermee() ?? false);
            @endphp

            <form action="{{ route('affectations.index') }}" method="GET" class="filter-form">
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

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Filtrer
                    </button>

                    <a href="{{ route('affectations.index') }}" class="btn">
                        Réinitialiser
                    </a>
                </div>
            </form>

            {{-- Condition : ! $creationVerrouillee. --}}
            @if (! $creationVerrouillee)
                <p>
                    <a href="{{ route('affectations.create') }}" class="btn btn-primary">
                        Ajouter une affectation
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
                        <th>Année</th>
                        <th>Classe</th>
                        <th>Matière</th>
                        <th>Enseignant</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les affectations dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($affectations as $affectation)
                        {{-- Preparation des donnees de la vue. --}}
                        @php
                            $verrouille = $affectation->classe?->anneeScolaire?->estFermee();
                        @endphp

                        <tr>
                            <td>{{ $affectation->classe?->anneeScolaire?->libelle ?? '-' }}</td>
                            <td>{{ $affectation->classe?->nom ?? '-' }}</td>
                            <td>{{ $affectation->matiere?->nom ?? '-' }}</td>
                            <td>{{ $affectation->enseignant?->name ?? '-' }}</td>
                            <td>{{ $affectation->date_debut?->format('d/m/Y') ?? '-' }}</td>
                            <td>{{ $affectation->date_fin?->format('d/m/Y') ?? '-' }}</td>

                            <td>
                                {{-- Condition : $affectation->statut === 'actif'. --}}
                                @if ($affectation->statut === 'actif')
                                    <span class="badge badge-success">Actif</span>
                                {{-- Sinon, autre cas prevu par la vue. --}}
                                @elseif ($affectation->statut === 'suspendu')
                                    <span class="badge badge-warning">Suspendu</span>
                                {{-- Sinon, affichage de l alternative prevue. --}}
                                @else
                                    <span class="badge">Terminé</span>
                                @endif
                            </td>

    

                            <td>
                                {{-- Condition : ! $verrouille. --}}
                                @if (! $verrouille)
                                    <a href="{{ route('affectations.edit', $affectation) }}" class="btn btn-primary">
                                        Modifier
                                    </a>

                                    {{-- Condition : $affectation->statut === 'actif'. --}}
                                    @if ($affectation->statut === 'actif')
                                        <form action="{{ route('affectations.terminer', $affectation) }}" method="POST" style="display:inline;">
                                            {{-- Jeton de securite du formulaire. --}}
                                            @csrf
                                            {{-- Methode HTTP du formulaire. --}}
                                            @method('PATCH')

                                            <button type="submit" class="btn btn-success">
                                                Terminer
                                            </button>
                                        </form>

                                        <form action="{{ route('affectations.suspendre', $affectation) }}" method="POST" style="display:inline;">
                                            {{-- Jeton de securite du formulaire. --}}
                                            @csrf
                                            {{-- Methode HTTP du formulaire. --}}
                                            @method('PATCH')

                                            <button type="submit" class="btn btn-danger">
                                                Suspendre
                                            </button>
                                        </form>
                                    {{-- Sinon, affichage de l alternative prevue. --}}
                                    @else
                                        <form action="{{ route('affectations.reactiver', $affectation) }}" method="POST" style="display:inline;">
                                            {{-- Jeton de securite du formulaire. --}}
                                            @csrf
                                            {{-- Methode HTTP du formulaire. --}}
                                            @method('PATCH')

                                            <button type="submit" class="btn btn-success">
                                                Réactiver
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Condition : $affectation->emploisDuTemps->isEmpty(). --}}
                                    @if ($affectation->emploisDuTemps->isEmpty())
                                        <form
                                            action="{{ route('affectations.destroy', $affectation) }}"
                                            method="POST"
                                            style="display:inline;"
                                            data-confirm="Voulez-vous vraiment supprimer cette affectation ?"
                                            data-confirm-title="Suppression d’une affectation"
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
                                    {{-- Sinon, affichage de l alternative prevue. --}}
                                    @else
                                        <span class="badge badge-warning">
                                            Suppression bloquée
                                        </span>
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
                            <td colspan="10">
                                Aucune affectation trouvée pour ce filtre.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
