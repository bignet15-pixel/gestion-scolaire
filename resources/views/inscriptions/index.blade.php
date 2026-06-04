<x-app-layout>
{{-- Vue Blade : resources/views/inscriptions/index.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Inscriptions</h1>

            {{-- Une inscription ne peut plus être ajoutée dans une année scolaire déjà fermée. --}}
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

            <form action="{{ route('inscriptions.index') }}" method="GET" class="filter-form filter-form-large">
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
                    <label class="form-label">Recherche ciblée</label>

                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        value="{{ $search }}"
                        placeholder="Matricule, nom, prénom ou contact parent"
                    >
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Filtrer
                    </button>

                    <a href="{{ route('inscriptions.index') }}" class="btn">
                        Réinitialiser
                    </a>
                </div>
            </form>

            {{-- Condition : ! $creationVerrouillee. --}}
            @if (! $creationVerrouillee)
                <p>
                    <a href="{{ route('inscriptions.create') }}" class="btn btn-primary">
                        Ajouter une inscription
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
                        <th>Élève</th>
                        <th>Matricule</th>
                        <th>Classe</th>
                        <th>Année</th>
                        <th>Date inscription</th>
                        <th>Reste à payé</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les inscriptions dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($inscriptions as $inscription)
                        {{-- Preparation des donnees de la vue. --}}
                        @php
                            $verrouille = $inscription->anneeScolaire?->estFermee();
                        @endphp

                        <tr>
                            <td>
                                {{ $inscription->eleve?->nom }}
                                {{ $inscription->eleve?->prenom }}
                            </td>

                            <td>{{ $inscription->eleve?->matricule }}</td>
                            <td>{{ $inscription->classe?->nom }}</td>
                            <td>{{ $inscription->anneeScolaire?->libelle }}</td>
                            <td>{{ $inscription->date_inscription?->format('d/m/Y') }}</td>

                            

                            <td>
                                {{ number_format($inscription->resteAPayer(), 0, ',', ' ') }} FCFA
                            </td>

                            <td>
                                {{-- Condition : $inscription->statut === 'actif'. --}}
                                @if ($inscription->statut === 'actif')
                                    <span class="badge badge-success">Actif</span>
                                {{-- Sinon, autre cas prevu par la vue. --}}
                                @elseif ($inscription->statut === 'termine')
                                    <span class="badge">Terminé</span>
                                {{-- Sinon, autre cas prevu par la vue. --}}
                                @elseif ($inscription->statut === 'abandonne')
                                    <span class="badge badge-danger">Abandonné</span>
                                {{-- Sinon, affichage de l alternative prevue. --}}
                                @else
                                    <span class="badge badge-warning">{{ $inscription->statut }}</span>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('inscriptions.show', $inscription) }}" class="btn btn-success">
                                    Détail
                                </a>

                                {{-- Condition : ! $verrouille. --}}
                                @if (! $verrouille)
                                    <a href="{{ route('inscriptions.edit', $inscription) }}" class="btn btn-primary">
                                        Modifier
                                    </a>

                                    {{-- Condition : $inscription->paiements->isEmpty() && $inscription->notes->isEmpty(). --}}
                                    @if ($inscription->paiements->isEmpty() && $inscription->notes->isEmpty())
                                        <form
                                            action="{{ route('inscriptions.destroy', $inscription) }}"
                                            method="POST"
                                            style="display:inline;"
                                            data-confirm="Voulez-vous vraiment supprimer cette inscription ?"
                                            data-confirm-title="Suppression d’une inscription"
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
                                Aucune inscription trouvée pour ce filtre.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
