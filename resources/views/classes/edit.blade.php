<x-app-layout>
{{-- Vue Blade : resources/views/classes/edit.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Modifier une classe</h1>

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

            <form action="{{ route('classes.update', $classe) }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf
                {{-- Methode HTTP du formulaire. --}}
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        {{-- Remplit la liste des annees scolaires. --}}
                        @foreach ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected(old('annee_scolaire_id', $classe->annee_scolaire_id) == $annee->id)>
                                {{ $annee->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Niveau</label>
                    <select name="niveau" class="form-control">
                        {{-- Affiche les elements de ['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2']. --}}
                        @foreach (['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'] as $niveau)
                            <option value="{{ $niveau }}" @selected(old('niveau', $classe->niveau) === $niveau)>
                                {{ $niveau }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nom de la classe</label>
                    <input type="text" name="nom" class="form-control" value="{{ old('nom', $classe->nom) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Frais de scolarité</label>
                    <input type="number" name="frais_scolarite" class="form-control" min="0" value="{{ old('frais_scolarite', $classe->frais_scolarite) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Enseignant principal</label>
                    <select name="enseignant_principal_id" class="form-control">
                        <option value="">Aucun</option>

                        {{-- Affiche les enseignants dans le tableau. --}}
                        @foreach ($enseignants as $enseignant)
                            <option value="{{ $enseignant->id }}" @selected(old('enseignant_principal_id', $classe->enseignant_principal_id) == $enseignant->id)>
                                {{ $enseignant->name }} — {{ $enseignant->matricule }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Chef de classe</label>
                    <select name="chef_classe_id" class="form-control">
                        <option value="">Aucun</option>

                        {{-- Remplit la liste des eleves disponibles. --}}
                        @foreach ($eleves as $eleve)
                            <option value="{{ $eleve->id }}" @selected(old('chef_classe_id', $classe->chef_classe_id) == $eleve->id)>
                                {{ $eleve->nom }} {{ $eleve->prenom }} — {{ $eleve->matricule }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Modifier
                </button>

                <a href="{{ route('classes.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>
