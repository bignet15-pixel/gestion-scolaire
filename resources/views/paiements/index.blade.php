<x-app-layout>
{{-- Vue Blade : resources/views/paiements/index.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Paiements</h1>

            <form action="{{ route('paiements.index') }}" method="GET" class="filter-form filter-form-large">
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

                    <a href="{{ route('paiements.index') }}" class="btn">
                        Réinitialiser
                    </a>
                </div>
            </form>

            <p>
                <a href="{{ route('paiements.create') }}" class="btn btn-primary">
                    Enregistrer un paiement
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
                        <th>Numéro</th>
                        <th>Élève</th>
                        <th>Matricule</th>
                        <th>Classe</th>
                        <th>Année</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les paiements dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($paiements as $paiement)
                        <tr>
                            <td>{{ $paiement->numero_paiement }}</td>

                            <td>
                                {{ $paiement->inscription?->eleve?->nom }}
                                {{ $paiement->inscription?->eleve?->prenom }}
                            </td>

                            <td>{{ $paiement->inscription?->eleve?->matricule ?? '-' }}</td>
                            <td>{{ $paiement->inscription?->classe?->nom ?? '-' }}</td>
                            <td>{{ $paiement->inscription?->anneeScolaire?->libelle ?? '-' }}</td>
                            <td>{{ $paiement->date_paiement?->format('d/m/Y') }}</td>
                            <td>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>

                            <td>
                                <a href="{{ route('paiements.show', $paiement) }}" class="btn btn-success">
                                    Détail
                                </a>

                                <a href="{{ route('paiements.recu', $paiement) }}" class="btn btn-primary">
                                    Reçu
                                </a>

                                <a href="{{ route('paiements.edit', $paiement) }}" class="btn btn-primary">
                                    Modifier
                                </a>

                                <form
                                    action="{{ route('paiements.destroy', $paiement) }}"
                                    method="POST"
                                    style="display:inline;"
                                    data-confirm="Voulez-vous vraiment supprimer ce paiement ? Le total payé et le reste à payer seront recalculés."
                                    data-confirm-title="Suppression d’un paiement"
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
                            </td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="8">
                                Aucun paiement trouvé pour ce filtre.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
