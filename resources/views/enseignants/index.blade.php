<x-app-layout>
{{-- Vue Blade : resources/views/enseignants/index.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Enseignants</h1>

            <p>
                <a href="{{ route('enseignants.create') }}" class="btn btn-primary">
                    Ajouter un enseignant
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
                    data-target="enseignants-table"
                    placeholder="Rechercher par matricule, nom, email, téléphone..."
                >
            </div>

            <table class="table" id="enseignants-table">
                <thead>
                    <tr>
                        <th>Matricule</th>
                        <th>Nom complet</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Affectations actives</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les enseignants dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($enseignants as $enseignant)
                        <tr>
                            <td>{{ $enseignant->matricule }}</td>
                            <td>{{ $enseignant->name }}</td>
                            <td>{{ $enseignant->email }}</td>
                            <td>{{ $enseignant->phone ?? '-' }}</td>
                            <td>{{ $enseignant->affectations_actives_count }}</td>

                            <td>
                                <a href="{{ route('enseignants.show', $enseignant) }}" class="btn btn-success">
                                    Détail
                                </a>

                                <a href="{{ route('enseignants.edit', $enseignant) }}" class="btn btn-primary">
                                    Modifier
                                </a>

                                {{-- Condition : $enseignant->affectations_actives_count == 0. --}}
                                @if ($enseignant->affectations_actives_count == 0)
                                    <form
                                        action="{{ route('enseignants.destroy', $enseignant) }}"
                                        method="POST"
                                        style="display:inline;"
                                        data-confirm="Voulez-vous vraiment désactiver cet enseignant ?"
                                        data-confirm-title="Désactivation d’un enseignant"
                                        data-confirm-button="Désactiver"
                                    >
                                        {{-- Jeton de securite du formulaire. --}}
                                        @csrf
                                        {{-- Methode HTTP du formulaire. --}}
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-danger">
                                            Désactiver
                                        </button>
                                    </form>
                                {{-- Sinon, affichage de l alternative prevue. --}}
                                @else
                                    <span class="badge badge-warning">
                                        Affecté
                                    </span>
                                @endif
                            </td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="6">
                                Aucun enseignant trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>