<x-app-layout>
    <div class="container">
        <div class="card">
            <h1>Appliquer une sanction manuelle</h1>

            <form action="{{ route('sanctions-appliquees.create') }}" method="GET" class="filter-form">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        @foreach ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected((string) $selectedAnneeId === (string) $annee->id)>{{ $annee->libelle }}{{ $annee->estFermee() ? ' — fermée' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Classe</label>
                    <select name="classe_id" class="form-control">
                        @foreach ($classes as $classe)
                            <option value="{{ $classe->id }}" @selected((string) $selectedClasseId === (string) $classe->id)>{{ $classe->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions"><button type="submit" class="btn btn-primary">Afficher</button></div>
            </form>

            @if ($selectedAnnee?->estFermee())
                <div class="alert alert-warning">Impossible d’appliquer une sanction dans une année scolaire fermée.</div>
            @endif

            @if ($inscriptions->isEmpty())
                <div class="alert alert-warning">Aucun élève actif n’est disponible dans cette classe.</div>
            @endif

            @if ($sanctions->isEmpty())
                <div class="alert alert-warning">
                    Aucune sanction manuelle ou mixte active n’est disponible. Créez d’abord une sanction utilisable pour appliquer une mesure.
                    <a href="{{ route('sanctions.create') }}" class="btn btn-primary">Créer une sanction</a>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if ($sanctions->isNotEmpty())
                <form action="{{ route('sanctions-appliquees.store') }}" method="POST" class="js-sanction-appliquee-form">
                    @csrf
                    <input type="hidden" name="annee_scolaire_id" value="{{ $selectedAnneeId }}">
                    <input type="hidden" name="classe_id" value="{{ $selectedClasseId }}">

                <div class="form-group">
                    <label class="form-label">Élève</label>
                    <select name="inscription_id" class="form-control">
                        @foreach ($inscriptions as $inscription)
                            <option value="{{ $inscription->id }}" @selected((string) old('inscription_id', $selectedInscriptionId) === (string) $inscription->id)>
                                {{ $inscription->eleve?->matricule }} — {{ $inscription->eleve?->nom }} {{ $inscription->eleve?->prenom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Sanction</label>
                    <select name="sanction_id" class="form-control js-sanction-appliquee-select" required>
                        @foreach ($sanctions as $sanction)
                            <option value="{{ $sanction->id }}" data-effet="{{ $sanction->type_effet }}" @selected(old('sanction_id') == $sanction->id)>
                                {{ $sanction->nom }} — {{ ucfirst($sanction->categorie) }} — {{ ucfirst(str_replace('_', ' ', $sanction->type_effet)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label js-sanction-trimestre-label">Trimestre</label>
                    <select name="trimestre_id" class="form-control js-sanction-trimestre">
                        <option value="">Aucun trimestre</option>
                        @foreach ($trimestres as $trimestre)
                            <option value="{{ $trimestre->id }}" @selected(old('trimestre_id') == $trimestre->id)>{{ $trimestre->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Date d’application</label>
                    <input type="date" name="date_application" class="form-control" value="{{ old('date_application', today()->format('Y-m-d')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Période début</label>
                    <input type="date" name="periode_debut" class="form-control" value="{{ old('periode_debut') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Période fin</label>
                    <input type="date" name="periode_fin" class="form-control" value="{{ old('periode_fin') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Motif</label>
                    <textarea name="motif" class="form-control" rows="3" required>{{ old('motif') }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Commentaire interne</label>
                    <textarea name="commentaire_interne" class="form-control" rows="3">{{ old('commentaire_interne') }}</textarea>
                </div>

                <div class="form-group">
                    <input type="hidden" name="visible_parent" value="0">
                    <label><input type="checkbox" name="visible_parent" value="1" @checked(old('visible_parent', false))> Visible par le parent</label>
                </div>

                    <button type="submit" class="btn btn-primary" @disabled($selectedAnnee?->estFermee() || $inscriptions->isEmpty())>Appliquer</button>
                    <a href="{{ route('sanctions-appliquees.index', ['annee_scolaire_id' => $selectedAnneeId, 'classe_id' => $selectedClasseId]) }}" class="btn">Retour</a>
                </form>
            @else
                <a href="{{ route('sanctions-appliquees.index', ['annee_scolaire_id' => $selectedAnneeId, 'classe_id' => $selectedClasseId]) }}" class="btn">Retour</a>
            @endif
        </div>
    </div>
</x-app-layout>
