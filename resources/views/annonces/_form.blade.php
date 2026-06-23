@csrf

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-grid">
    <div class="form-group">
        <label for="titre">Titre</label>
        <input
            type="text"
            name="titre"
            id="titre"
            class="form-control"
            value="{{ old('titre', $annonce->titre ?? '') }}"
            required
        >
    </div>

    <div class="form-group">
        <label for="type">Type d’annonce</label>
        <select name="type" id="type" class="form-control" required>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $annonce->type ?? 'information') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="priorite">Priorité</label>
        <select name="priorite" id="priorite" class="form-control" required>
            @foreach ($priorites as $value => $label)
                <option value="{{ $value }}" @selected(old('priorite', $annonce->priorite ?? 'normale') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="cible">Destinataires</label>
        <select name="cible" id="cible" class="form-control" required>
            @foreach ($cibles as $value => $label)
                <option value="{{ $value }}" @selected(old('cible', $annonce->cible ?? 'parents') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <small>Si vous choisissez une classe, les parents des élèves de cette classe et les enseignants liés seront concernés.</small>
    </div>

    <div class="form-group">
        <label for="classe_id">Classe concernée</label>
        <select name="classe_id" id="classe_id" class="form-control">
            <option value="">Aucune classe précise</option>
            @foreach ($classes as $classe)
                <option value="{{ $classe->id }}" @selected((string) old('classe_id', $annonce->classe_id ?? '') === (string) $classe->id)>
                    {{ $classe->nom }} — {{ $classe->anneeScolaire?->libelle ?? 'année non définie' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="date_expiration">Date d’expiration</label>
        <input
            type="date"
            name="date_expiration"
            id="date_expiration"
            class="form-control"
            value="{{ old('date_expiration', isset($annonce) && $annonce->date_expiration ? $annonce->date_expiration->format('Y-m-d') : '') }}"
        >
        <small>Laissez vide si l’annonce doit rester visible.</small>
    </div>
</div>

<div class="form-group">
    <label for="contenu">Contenu complet de l’annonce</label>
    <textarea
        name="contenu"
        id="contenu"
        class="form-control"
        rows="8"
        required
    >{{ old('contenu', $annonce->contenu ?? '') }}</textarea>
    <small>Ce contenu sera envoyé intégralement par email aux destinataires lors de la publication.</small>
</div>

<div class="form-actions">
    <button type="submit" name="action" value="brouillon" class="btn">
        Enregistrer en brouillon
    </button>

    @if (! isset($annonce) || ! $annonce->est_publiee)
        <button type="submit" name="action" value="publier" class="btn btn-primary">
            Publier et envoyer les emails
        </button>
    @endif
</div>
