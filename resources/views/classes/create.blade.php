<x-app-layout>
{{-- Vue Blade : resources/views/classes/create.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Ajouter une classe</h1>

            <form action="{{ route('classes.create') }}" method="GET" class="filter-form">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        @foreach ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected((string) $selectedAnneeId === (string) $annee->id)>
                                {{ $annee->libelle }}{{ $annee->estFermee() ? ' — fermée' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <input type="hidden" name="niveau" value="{{ $selectedNiveau }}">

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Afficher
                    </button>
                </div>
            </form>

            @if (! $selectedAnnee)
                <div class="alert alert-warning">
                    Aucune année scolaire n’est disponible pour créer une classe.
                </div>
            @elseif ($selectedAnnee->estFermee())
                <div class="alert alert-warning">
                    Cette année scolaire est fermée. Ses classes sont disponibles uniquement dans l’historique.
                </div>
            @endif

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

            <form action="{{ route('classes.store') }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf

                <input type="hidden" name="annee_scolaire_id" value="{{ $selectedAnneeId }}">

                <div class="form-group">
                    <label class="form-label">Niveau</label>
                    <select name="niveau" class="form-control">
                        {{-- Affiche les elements de ['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2']. --}}
                        @foreach (['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'] as $niveau)
                            <option value="{{ $niveau }}" @selected(old('niveau', $selectedNiveau) === $niveau)>
                                {{ $niveau }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nom de la classe</label>
                    <input type="text" name="nom" class="form-control" placeholder="Ex: CM2 A" value="{{ old('nom') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Frais de scolarité</label>
                    <input type="number" name="frais_scolarite" class="form-control" min="0" value="{{ old('frais_scolarite', 0) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Enseignant principal</label>
                    <select name="enseignant_principal_id" class="form-control">
                        <option value="">Aucun</option>

                        {{-- Affiche les enseignants dans le tableau. --}}
                        @foreach ($enseignants as $enseignant)
                            <option value="{{ $enseignant->id }}" @selected(old('enseignant_principal_id') == $enseignant->id)>
                                {{ $enseignant->name }} — {{ $enseignant->matricule }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" @disabled(! $selectedAnnee || $selectedAnnee->estFermee())>
                    Enregistrer
                </button>

                <a
                    href="{{ route('classes.index', [
                        'annee_scolaire_id' => $selectedAnneeId,
                        'niveau' => $selectedNiveau,
                    ]) }}"
                    class="btn"
                >
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>
