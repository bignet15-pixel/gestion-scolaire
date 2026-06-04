<x-app-layout>
{{-- Vue Blade : resources/views/eleves/index.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Élèves</h1>

            <form action="{{ route('eleves.index') }}" method="GET" class="filter-form">
                <div class="form-group">
                    <label class="form-label">Année d'inscription</label>

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

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Filtrer
                    </button>

                    <a href="{{ route('eleves.index') }}" class="btn">
                        Réinitialiser
                    </a>
                </div>
            </form>

            <p>
                <a href="{{ route('eleves.create') }}" class="btn btn-primary">
                    Ajouter un élève
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

            <div class="table-search-box">
                <label>Recherche rapide</label>

                <input
                    type="text"
                    class="form-control js-table-search"
                    data-target="eleves-table"
                    placeholder="Rechercher par matricule, nom, prénom, contact..."
                >
            </div>

            <table class="table" id="eleves-table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Matricule</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Sexe</th>
                        <th>Contact parent</th>
                        <th>Inscriptions</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les eleves dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($eleves as $eleve)
                        <tr>
                            <td>
                                {{-- Condition : $eleve->photo. --}}
                                @if ($eleve->photo)
                                    <img src="{{ asset('storage/' . $eleve->photo) }}" alt="Photo" width="45">
                                {{-- Sinon, affichage de l alternative prevue. --}}
                                @else
                                    -
                                @endif
                            </td>

                            <td>{{ $eleve->matricule }}</td>
                            <td>{{ $eleve->nom }}</td>
                            <td>{{ $eleve->prenom }}</td>
                            <td>{{ $eleve->sexe }}</td>
                            <td>{{ $eleve->contact_parent ?? '-' }}</td>
                            <td>{{ $eleve->inscriptions_count }}</td>

                            <td>
                                <a href="{{ route('eleves.show', $eleve) }}" class="btn btn-success">
                                    Détail
                                </a>

                                <a href="{{ route('eleves.edit', $eleve) }}" class="btn btn-primary">
                                    Modifier
                                </a>

                                {{-- Condition : $eleve->inscriptions_count == 0. --}}
                                @if ($eleve->inscriptions_count == 0)
                                    <form
                                        action="{{ route('eleves.destroy', $eleve) }}"
                                        method="POST"
                                        style="display:inline;"
                                        data-confirm="Voulez-vous vraiment supprimer cet élève ?"
                                        data-confirm-title="Suppression d’un élève"
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
                                        Inscrit
                                    </span>
                                @endif
                            </td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="8">
                                Aucun élève trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>