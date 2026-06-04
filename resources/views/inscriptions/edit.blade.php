<x-app-layout>
{{-- Vue Blade : resources/views/inscriptions/edit.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Modifier une inscription</h1>

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

            <form action="{{ route('inscriptions.update', $inscription) }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf
                {{-- Methode HTTP du formulaire. --}}
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Élève</label>
                    <select name="eleve_id" class="form-control">
                        {{-- Remplit la liste des eleves disponibles. --}}
                        @foreach ($eleves as $eleve)
                            <option value="{{ $eleve->id }}" @selected(old('eleve_id', $inscription->eleve_id) == $eleve->id)>
                                {{ $eleve->matricule }} — {{ $eleve->nom }} {{ $eleve->prenom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        {{-- Remplit la liste des annees scolaires. --}}
                        @foreach ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected(old('annee_scolaire_id', $inscription->annee_scolaire_id) == $annee->id)>
                                {{ $annee->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Classe</label>
                    <select name="classe_id" class="form-control">
                        {{-- Remplit la liste des classes disponibles. --}}
                        @foreach ($classes as $classe)
                            <option value="{{ $classe->id }}" @selected(old('classe_id', $inscription->classe_id) == $classe->id)>
                                {{ $classe->nom }} — {{ $classe->anneeScolaire->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Date inscription</label>
                    <input type="date" name="date_inscription" class="form-control" value="{{ old('date_inscription', $inscription->date_inscription?->format('Y-m-d')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Frais attendus</label>
                    <input type="number" name="frais_attendu" class="form-control" min="0" value="{{ old('frais_attendu', $inscription->frais_attendu) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-control">
                        <option value="actif" @selected(old('statut', $inscription->statut) === 'actif')>Actif</option>
                        <option value="termine" @selected(old('statut', $inscription->statut) === 'termine')>Terminé</option>
                        <option value="abandonne" @selected(old('statut', $inscription->statut) === 'abandonne')>Abandonné</option>
                        <option value="transfere" @selected(old('statut', $inscription->statut) === 'transfere')>Transféré</option>
                    </select>
                </div>

                <div class="alert alert-warning">
                    Le passage en classe supérieure est autorisé uniquement si l’élève a validé
                    son année précédente avec une moyenne annuelle supérieure ou égale à 10/20.
                    Sinon, il doit être réinscrit dans le même niveau.
                </div>

                <button type="submit" class="btn btn-primary">
                    Modifier
                </button>

                <a href="{{ route('inscriptions.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>