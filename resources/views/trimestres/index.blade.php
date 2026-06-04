<x-app-layout>
{{-- Vue Blade : resources/views/trimestres/index.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Trimestres</h1>

            <p>
                <a href="{{ route('trimestres.create') }}" class="btn btn-primary">
                    Ajouter un trimestre
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
                        <th>Année scolaire</th>
                        <th>Date début</th>
                        <th>Date fin</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les trimestres dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($trimestres as $trimestre)
                        {{-- Preparation des donnees de la vue. --}}
                        @php
                            $verrouille = $trimestre->statut === 'ferme' || $trimestre->anneeScolaire?->estFermee();
                        @endphp

                        <tr>
                            <td>{{ $trimestre->nom }}</td>
                            <td>{{ $trimestre->anneeScolaire->libelle }}</td>
                            <td>{{ $trimestre->date_debut?->format('d/m/Y') }}</td>
                            <td>{{ $trimestre->date_fin?->format('d/m/Y') }}</td>
                            <td>{{ $trimestre->statut }}</td>
                            <td>
                                {{-- Condition : ! $verrouille. --}}
                                @if (! $verrouille)
                                    <a href="{{ route('trimestres.edit', $trimestre) }}" class="btn btn-primary">
                                        Modifier
                                    </a>

                                    <form action="{{ route('trimestres.fermer', $trimestre) }}" method="POST" style="display:inline;">
                                        {{-- Jeton de securite du formulaire. --}}
                                        @csrf
                                        {{-- Methode HTTP du formulaire. --}}
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-success">
                                            Fermer
                                        </button>
                                    </form>

                                    <form action="{{ route('trimestres.destroy', $trimestre) }}" method="POST" style="display:inline;">
                                        {{-- Jeton de securite du formulaire. --}}
                                        @csrf
                                        {{-- Methode HTTP du formulaire. --}}
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Supprimer ce trimestre ?')">
                                            Supprimer
                                        </button>
                                    </form>
                                {{-- Sinon, affichage de l alternative prevue. --}}
                                @else
                                    <span class="badge">Historique</span>
                                @endif
                            </td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="6">Aucun trimestre trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
