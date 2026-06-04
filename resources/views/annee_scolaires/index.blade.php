<x-app-layout>
{{-- Vue Blade : resources/views/annee_scolaires/index.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Années scolaires</h1>

            <p>
                <a href="{{ route('annee-scolaires.create') }}" class="btn btn-primary">
                    Ajouter une année scolaire
                </a>
            </p>

            {{-- Condition : session('success'). --}}
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <table class="table">
                <thead>
                    <tr>
                        <th>Libellé</th>
                        <th>Date début</th>
                        <th>Date fin</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les annees scolaires dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($annees as $annee)
                        <tr>
                            <td>{{ $annee->libelle }}</td>
                            <td>{{ $annee->date_debut->format('d/m/Y') }}</td>
                            <td>{{ $annee->date_fin->format('d/m/Y') }}</td>
                            <td>{{ $annee->statut }}</td>
                            <td>
                                <a href="{{ route('annee-scolaires.edit', $annee) }}" class="btn btn-primary">
                                    Modifier
                                </a>

                                {{-- Condition : $annee->statut !== 'active'. --}}
                                @if ($annee->statut !== 'active')
                                    <form action="{{ route('annee-scolaires.activer', $annee) }}" method="POST" style="display:inline;">
                                        {{-- Jeton de securite du formulaire. --}}
                                        @csrf
                                        {{-- Methode HTTP du formulaire. --}}
                                        @method('PATCH')
                                        <button class="btn btn-success" type="submit">
                                            Activer
                                        </button>
                                    </form>
                                @endif

                                {{-- Condition : $annee->statut !== 'fermee'. --}}
                                @if ($annee->statut !== 'fermee')
                                    <form action="{{ route('annee-scolaires.fermer', $annee) }}" method="POST" style="display:inline;">
                                        {{-- Jeton de securite du formulaire. --}}
                                        @csrf
                                        {{-- Methode HTTP du formulaire. --}}
                                        @method('PATCH')
                                        <button class="btn btn-danger" type="submit">
                                            Fermer
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('annee-scolaires.destroy', $annee) }}" 
                                    method="POST" 
                                    style="display:inline;"
                                    data-confirm="Voulez-vous vraiment supprimer l'année {{ $annee->libelle }} ?"
                                    data-confirm-title="Suppression d’une année scolaire"
                                    data-confirm-button="Supprimer"
                                    >
                                    {{-- Jeton de securite du formulaire. --}}
                                    @csrf
                                    {{-- Methode HTTP du formulaire. --}}
                                    @method('DELETE')
                                    <button class="btn btn-danger" type="submit"                                         
                                    >
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="5">Aucune année scolaire trouvée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>