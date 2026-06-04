<x-app-layout>
{{-- Vue Blade : resources/views/paiements/create.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Enregistrer un paiement</h1>

            <form action="{{ route('paiements.create') }}" method="GET" class="filter-form filter-form-large">
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
                        Rechercher
                    </button>

                    <a href="{{ route('paiements.create') }}" class="btn">
                        Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Nouveau paiement</h2>

            {{-- Condition : $errors->any(). --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        {{-- Affiche les messages d erreur de validation. --}}
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('paiements.store') }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf

                <div class="form-group">
                    <label class="form-label">Inscription concernée</label>

                    <select name="inscription_id" class="form-control">
                        {{-- Affiche les inscriptions dans le tableau, ou le message vide si aucun resultat n existe. --}}
                        @forelse ($inscriptions as $inscription)
                            <option
                                value="{{ $inscription->id }}"
                                @selected(
                                    old('inscription_id', $selectedInscriptionId) == $inscription->id
                                )
                            >
                                {{ $inscription->eleve?->matricule }}
                                —
                                {{ $inscription->eleve?->nom }} {{ $inscription->eleve?->prenom }}
                                —
                                {{ $inscription->classe?->nom }}
                                —
                                {{ $inscription->anneeScolaire?->libelle }}
                                —
                                Reste : {{ number_format($inscription->resteAPayer(), 0, ',', ' ') }} FCFA
                            </option>
                        {{-- Message affiche quand la liste est vide. --}}
                        @empty
                            <option value="">
                                Aucune inscription trouvée. Utilisez la recherche ciblée.
                            </option>
                        @endforelse
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Montant payé</label>
                    <input
                        type="number"
                        name="montant"
                        class="form-control"
                        min="1"
                        value="{{ old('montant') }}"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">Date paiement</label>
                    <input
                        type="date"
                        name="date_paiement"
                        class="form-control"
                        value="{{ old('date_paiement', date('Y-m-d')) }}"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">Mode de paiement</label>

                    <select name="mode_paiement" class="form-control">
                        <option value="especes" @selected(old('mode_paiement') === 'especes')>
                            Espèces
                        </option>

                        <option value="mobile_money" @selected(old('mode_paiement') === 'mobile_money')>
                            Mobile money
                        </option>

                        <option value="virement" @selected(old('mode_paiement') === 'virement')>
                            Virement
                        </option>

                        <option value="autre" @selected(old('mode_paiement') === 'autre')>
                            Autre
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" @disabled($inscriptions->isEmpty())>
                    Enregistrer
                </button>

                <a href="{{ route('paiements.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>