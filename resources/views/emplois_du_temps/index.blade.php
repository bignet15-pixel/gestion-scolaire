<x-app-layout>
{{-- Vue Blade : resources/views/emplois_du_temps/index.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Emploi du temps</h1>

            {{-- L'emploi du temps d'une année fermée reste visible, sans nouvelle saisie. --}}
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

            {{-- Condition : ! $creationVerrouillee. --}}
            @if (! $creationVerrouillee)
                <p>
                    <a href="{{ route('emplois-du-temps.create') }}" class="btn btn-primary">
                        Ajouter un créneau
                    </a>
                </p>
            @endif

            {{-- Condition : session('success'). --}}
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('emplois-du-temps.index') }}" method="GET">
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
                    <label class="form-label">Enseignant</label>
                    <select name="enseignant_id" class="form-control">
                        <option value="">Tous les enseignants</option>

                        {{-- Affiche les enseignants dans le tableau. --}}
                        @foreach ($enseignants as $enseignant)
                            <option value="{{ $enseignant->id }}" @selected((string) $selectedEnseignantId === (string) $enseignant->id)>
                                {{ $enseignant->name }} — {{ $enseignant->matricule }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Filtrer
                </button>

                <a href="{{ route('emplois-du-temps.index') }}" class="btn">
                    Réinitialiser
                </a>
            </form>
        </div>

        <div class="card">
            <h2>Liste des créneaux</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Jour</th>
                        <th>Heure</th>
                        <th>Année</th>
                        <th>Classe</th>
                        <th>Matière</th>
                        <th>Enseignant</th>
                        <th>Salle</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les creneaux dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($emplois as $emploi)
                        {{-- Preparation des donnees de la vue. --}}
                        @php
                            $verrouille = $emploi->affectation?->classe?->anneeScolaire?->estFermee();
                        @endphp

                        <tr>
                            <td>{{ ucfirst($emploi->jour) }}</td>

                            <td>
                                {{ $emploi->heure_debut->format('H:i') }}
                                -
                                {{ $emploi->heure_fin->format('H:i') }}
                            </td>

                            <td>{{ $emploi->affectation?->classe?->anneeScolaire?->libelle ?? '-' }}</td>
                            <td>{{ $emploi->affectation?->classe?->nom ?? 'Affectation introuvable' }}</td>
                            <td>{{ $emploi->affectation?->matiere?->nom ?? '-' }}</td>
                            <td>{{ $emploi->affectation?->enseignant?->name ?? '-' }}</td>
                            <td>{{ $emploi->salle ?? '-' }}</td>

                            <td>
                                <a href="{{ route('emplois-du-temps.show', $emploi) }}" class="btn btn-success">
                                    Détail
                                </a>

                                {{-- Condition : ! $verrouille. --}}
                                @if (! $verrouille)
                                    <a href="{{ route('emplois-du-temps.edit', $emploi) }}" class="btn btn-primary">
                                        Modifier
                                    </a>

                                    <form action="{{ route('emplois-du-temps.destroy', $emploi) }}" method="POST" style="display:inline;">
                                        {{-- Jeton de securite du formulaire. --}}
                                        @csrf
                                        {{-- Methode HTTP du formulaire. --}}
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Supprimer ce créneau ?')">
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
                            <td colspan="8">Aucun créneau trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
