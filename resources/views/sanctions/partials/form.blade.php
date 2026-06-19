<div class="form-group">
    <label class="form-label">Nom</label>
    <input type="text" name="nom" class="form-control" value="{{ old('nom', $sanction?->nom) }}" required>
</div>

<div class="form-group">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description', $sanction?->description) }}</textarea>
</div>

<div class="form-group">
    <label class="form-label">Catégorie</label>
    <select name="categorie" class="form-control js-sanction-categorie">
        @foreach (['absence', 'retard', 'conduite'] as $categorie)
            <option value="{{ $categorie }}" @selected(old('categorie', $sanction?->categorie) === $categorie)>{{ ucfirst($categorie) }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label class="form-label">Mode de déclenchement</label>
    <select name="mode_declenchement" class="form-control js-sanction-mode">
        @foreach (['automatique', 'manuel', 'mixte'] as $mode)
            <option value="{{ $mode }}" @selected(old('mode_declenchement', $sanction?->mode_declenchement) === $mode)>{{ ucfirst($mode) }}</option>
        @endforeach
    </select>
</div>

<div class="form-group" data-sanction-automatique>
    <label class="form-label">Statut déclencheur</label>
    <select name="statut_declencheur" class="form-control">
        @foreach (['tous', 'en_attente', 'non_justifiee', 'refusee'] as $statut)
            <option value="{{ $statut }}" @selected(old('statut_declencheur', $sanction?->statut_declencheur ?? 'tous') === $statut)>
                {{ ucfirst(str_replace('_', ' ', $statut)) }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group" data-sanction-automatique>
    <label class="form-label">Seuil d’événements</label>
    <input type="number" name="seuil" class="form-control" min="1" max="1000" value="{{ old('seuil', $sanction?->seuil) }}">
</div>

<div class="form-group" data-sanction-automatique>
    <label class="form-label">Période de calcul</label>
    <select name="periode_calcul" class="form-control">
        <option value="">Non applicable</option>
        @foreach (['semaine', 'mois', 'trimestre', 'annee'] as $periode)
            <option value="{{ $periode }}" @selected(old('periode_calcul', $sanction?->periode_calcul) === $periode)>{{ ucfirst($periode) }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label class="form-label">Niveau de gravité</label>
    <select name="niveau_gravite" class="form-control">
        @foreach (['faible', 'moyen', 'grave'] as $niveau)
            <option value="{{ $niveau }}" @selected(old('niveau_gravite', $sanction?->niveau_gravite ?? 'faible') === $niveau)>{{ ucfirst($niveau) }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label class="form-label">Type d’effet</label>
    <select name="type_effet" class="form-control js-sanction-effet">
        @foreach (['appel_parent', 'convocation_administration', 'points_en_moins', 'avertissement', 'autre'] as $effet)
            <option value="{{ $effet }}" @selected(old('type_effet', $sanction?->type_effet) === $effet)>{{ ucfirst(str_replace('_', ' ', $effet)) }}</option>
        @endforeach
    </select>
</div>

<div class="form-group" data-sanction-valeur>
    <label class="form-label">Valeur de l’effet</label>
    <input type="number" name="valeur_effet" class="form-control js-sanction-valeur" min="0.01" max="10000" step="0.01" value="{{ old('valeur_effet', $sanction?->valeur_effet) }}">
</div>

<div class="form-group">
    <input type="hidden" name="active" value="0">
    <label><input type="checkbox" name="active" value="1" @checked(old('active', $sanction?->active ?? true))> Sanction active</label>
</div>

<div class="form-group">
    <input type="hidden" name="visible_parent_defaut" value="0">
    <label><input type="checkbox" name="visible_parent_defaut" value="1" @checked(old('visible_parent_defaut', $sanction?->visible_parent_defaut ?? false))> Visible par le parent par défaut</label>
</div>
