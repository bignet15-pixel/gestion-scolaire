<x-app-layout>
    <div class="container">
        <div class="card">
            <h1>Ajouter une inscription</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                action="{{ route('inscriptions.store') }}"
                method="POST"
                class="js-inscription-form"
                data-options-url="{{ route('inscriptions.options') }}"
            >
                @csrf

                <div class="form-group">
                    <label class="form-label">Année scolaire</label>

                    <select name="annee_scolaire_id" class="form-control js-inscription-annee">
                        @forelse ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected((string) old('annee_scolaire_id', $selectedAnneeId) === (string) $annee->id)>
                                {{ $annee->libelle }}
                            </option>
                        @empty
                            <option value="">Aucune année scolaire disponible</option>
                        @endforelse
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Classe</label>

                    <select
                        name="classe_id"
                        class="form-control js-inscription-classe"
                        data-selected="{{ old('classe_id', $selectedClasseId) }}"
                    >
                        @forelse ($classes as $classe)
                            <option
                                value="{{ $classe->id }}"
                                data-frais="{{ (float) $classe->frais_scolarite }}"
                                @selected((string) old('classe_id', $selectedClasseId) === (string) $classe->id)
                            >
                                {{ $classe->nom }} — {{ $classe->anneeScolaire->libelle }}
                                — frais : {{ number_format($classe->frais_scolarite, 0, ',', ' ') }} FCFA
                            </option>
                        @empty
                            <option value="">Aucune classe disponible pour cette année</option>
                        @endforelse
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Élève admissible</label>

                    <select
                        name="eleve_id"
                        class="form-control js-inscription-eleve"
                        data-selected="{{ old('eleve_id', $selectedEleveId) }}"
                    >
                        @forelse ($eleves as $eleve)
                            <option value="{{ $eleve->id }}" @selected((string) old('eleve_id', $selectedEleveId) === (string) $eleve->id)>
                                {{ $eleve->matricule }} — {{ $eleve->nom }} {{ $eleve->prenom }}
                            </option>
                        @empty
                            <option value="">Aucun élève admissible pour cette classe</option>
                        @endforelse
                    </select>

                    <small class="js-inscription-options-message">
                        Les élèves déjà inscrits, avec impayés ou non admissibles au niveau choisi ne sont pas affichés.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Date inscription</label>
                    <input type="date" name="date_inscription" class="form-control" value="{{ old('date_inscription', date('Y-m-d')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Frais attendus</label>
                    <input type="number" name="frais_attendu" class="form-control js-inscription-frais" min="0" value="{{ old('frais_attendu') }}">
                    <small>Laisser vide pour utiliser automatiquement les frais de la classe.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-control">
                        <option value="actif" @selected(old('statut', 'actif') === 'actif')>Actif</option>
                        <option value="termine" @selected(old('statut') === 'termine')>Terminé</option>
                        <option value="abandonne" @selected(old('statut') === 'abandonne')>Abandonné</option>
                        <option value="transfere" @selected(old('statut') === 'transfere')>Transféré</option>
                    </select>
                </div>

                <div class="alert alert-warning">
                    Le passage en classe supérieure est autorisé uniquement si l’élève a validé
                    son année précédente avec une moyenne annuelle supérieure ou égale à 10/20.
                    Sinon, il doit être réinscrit dans le même niveau.
                </div>

                <button
                    type="submit"
                    class="btn btn-primary js-inscription-submit"
                    @disabled($annees->isEmpty() || $classes->isEmpty() || $eleves->isEmpty())
                >
                    Enregistrer
                </button>

                <a href="{{ route('inscriptions.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>
