<x-app-layout>
{{-- Vue Blade : resources/views/classes/index.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Classes</h1>

            {{-- Si l'année filtrée est fermée, la liste reste consultable mais la création est masquée. --}}
            {{-- Preparation des donnees de la vue. --}}
            @php
                $selectedAnnee = $selectedAnneeId
                    ? $annees->first(fn ($annee) => (string) $annee->id === (string) $selectedAnneeId)
                    : null;

                $creationVerrouillee = $selectedAnnee?->estFermee() ?? false;
            @endphp

            <form action="{{ route('classes.index') }}" method="GET" class="filter-form">
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
                    <label class="form-label">Niveau</label>
                    <select name="niveau" class="form-control">
                        <option value="">Tous les niveaux</option>

                        {{-- Remplit la liste des niveaux scolaires. --}}
                        @foreach ($niveaux as $niveau)
                            <option value="{{ $niveau }}" @selected($selectedNiveau === $niveau)>
                                {{ $niveau }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Filtrer
                    </button>

                    <a href="{{ route('classes.index') }}" class="btn">
                        Réinitialiser
                    </a>
                </div>
            </form>

            {{-- Condition : ! $creationVerrouillee. --}}
            @if (! $creationVerrouillee)
                <p>
                    <a href="{{ route('classes.create') }}" class="btn btn-primary">
                        Ajouter une classe
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

            <div class="table-search-box">
                <label>Recherche rapide</label>

                <input
                    type="text"
                    class="form-control js-table-search"
                    data-target="classes-table"
                    placeholder="Rechercher par année, niveau, nom, enseignant..."
                >
            </div>

            <table class="table" id="classes-table">
                <thead>
                    <tr>
                        <th>Année scolaire</th>
                        <th>Niveau</th>
                        <th>Nom</th>
                        <th>Frais</th>
                        <th>Enseignant principal</th>
                        <th>Élèves</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les classes dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($classes as $classe)
                        {{-- Preparation des donnees de la vue. --}}
                        @php
                            $verrouille = $classe->anneeScolaire?->estFermee();
                        @endphp

                        <tr>
                            <td>{{ $classe->anneeScolaire->libelle }}</td>
                            <td>{{ $classe->niveau }}</td>
                            <td>{{ $classe->nom }}</td>
                            <td>{{ number_format($classe->frais_scolarite, 0, ',', ' ') }} FCFA</td>
                            <td>{{ $classe->enseignantPrincipal?->name ?? 'Non affecté' }}</td>
                            <td>{{ $classe->inscriptions_count }}</td>

                            <td>
                                <a href="{{ route('classes.show', $classe) }}" class="btn btn-success">
                                    Détail
                                </a>

                                {{-- Condition : ! $verrouille. --}}
                                @if (! $verrouille)
                                    <a href="{{ route('classes.edit', $classe) }}" class="btn btn-primary">
                                        Modifier
                                    </a>

                                    {{-- Condition : $classe->inscriptions_count == 0. --}}
                                    @if ($classe->inscriptions_count == 0)
                                        <form
                                            action="{{ route('classes.destroy', $classe) }}"
                                            method="POST"
                                            style="display:inline;"
                                            data-confirm="Voulez-vous vraiment supprimer cette classe ? Cette action sera refusée si la classe contient déjà des affectations ou des évaluations."
                                            data-confirm-title="Suppression d’une classe"
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
                                          Classe active
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
                            <td colspan="7">
                                Aucune classe trouvée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
