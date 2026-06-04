<x-app-layout>
{{-- Vue Blade : resources/views/matieres/index.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Matières</h1>

            <form action="{{ route('matieres.index') }}" method="GET" class="filter-form">
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

                    <a href="{{ route('matieres.index') }}" class="btn">
                        Réinitialiser
                    </a>
                </div>
            </form>

            <p>
                <a href="{{ route('matieres.create') }}" class="btn btn-primary">
                    Ajouter une matière
                </a>
            </p>

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
                        <th>Nom</th>
                        <th>Coefficient par défaut</th>
                        <th>Affectations</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les matieres dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($matieres as $matiere)
                        <tr>
                            <td>{{ $matiere->nom }}</td>
                            <td>{{ $matiere->coefficient_default }}</td>
                            <td>{{ $matiere->affectations_count }}</td>

                            <td>
                                <a href="{{ route('matieres.edit', $matiere) }}" class="btn btn-primary">
                                    Modifier
                                </a>

                                {{-- Condition : $matiere->affectations_count == 0. --}}
                                @if ($matiere->affectations_count == 0)
                                    <form
                                        action="{{ route('matieres.destroy', $matiere) }}"
                                        method="POST"
                                        style="display:inline;"
                                        data-confirm="Voulez-vous vraiment supprimer cette matière ?"
                                        data-confirm-title="Suppression d’une matière"
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
                                        Utilisée
                                    </span>
                                @endif
                            </td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="4">
                                Aucune matière trouvée pour ce filtre.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>