<x-app-layout>
    <div class="container">
        <div class="card">
            <h1>Enregistrer une absence ou un retard</h1>

            <form action="{{ route('absences-retards.create') }}" method="GET" class="filter-form filter-form-large">
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

                <div class="form-group">
                    <label class="form-label">Classe</label>
                    <select name="classe_id" class="form-control">
                        @forelse ($classes as $classe)
                            <option value="{{ $classe->id }}" @selected((string) $selectedClasseId === (string) $classe->id)>
                                {{ $classe->nom }}
                            </option>
                        @empty
                            <option value="">Aucune classe disponible</option>
                        @endforelse
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Afficher</button>
                </div>
            </form>

            @if ($selectedAnnee?->estFermee())
                <div class="alert alert-warning">Cette année est fermée : son assiduité est consultable uniquement en historique.</div>
            @elseif ($inscriptions->isEmpty())
                <div class="alert alert-warning">Aucun élève actif n’est inscrit dans cette classe.</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('absences-retards.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="annee_scolaire_id" value="{{ $selectedAnneeId }}">
                <input type="hidden" name="classe_id" value="{{ $selectedClasseId }}">

                <div class="form-group">
                    <label class="form-label">Élève</label>
                    <select name="inscription_id" class="form-control" @disabled($inscriptions->isEmpty())>
                        @foreach ($inscriptions as $inscription)
                            <option value="{{ $inscription->id }}" @selected((string) old('inscription_id', $selectedInscriptionId) === (string) $inscription->id)>
                                {{ $inscription->eleve?->matricule }} — {{ $inscription->eleve?->nom }} {{ $inscription->eleve?->prenom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @include('absences_retards.partials.form', ['evenement' => null])

                <button type="submit" class="btn btn-primary" @disabled($selectedAnnee?->estFermee() || $inscriptions->isEmpty())>
                    Enregistrer
                </button>
                <a href="{{ route('absences-retards.index', ['annee_scolaire_id' => $selectedAnneeId, 'classe_id' => $selectedClasseId]) }}" class="btn">Retour</a>
            </form>
        </div>
    </div>
</x-app-layout>
