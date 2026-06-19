@php
    $anneeFormulaire = $selectedAnnee ?? $evenement?->inscription?->anneeScolaire;
    $dateReference = now()->startOfDay();

    if ($anneeFormulaire?->date_debut && $dateReference->lt($anneeFormulaire->date_debut)) {
        $dateReference = $anneeFormulaire->date_debut->copy();
    }

    if ($anneeFormulaire?->date_fin && $dateReference->gt($anneeFormulaire->date_fin)) {
        $dateReference = $anneeFormulaire->date_fin->copy();
    }

    $dateDebutValeur = old('date_debut', $evenement?->date_debut?->format('Y-m-d') ?? $dateReference->format('Y-m-d'));
    $dateFinValeur = old('date_fin', $evenement?->date_fin?->format('Y-m-d') ?? $dateDebutValeur);
@endphp

<div class="form-group">
    <label class="form-label">Type</label>
    <select name="type" class="form-control js-assiduite-type">
        <option value="absence" @selected(old('type', $evenement?->type) === 'absence')>Absence</option>
        <option value="retard" @selected(old('type', $evenement?->type) === 'retard')>Retard</option>
    </select>
</div>

<div class="form-group">
    <label class="form-label">Date début</label>
    <input
        type="date"
        name="date_debut"
        class="form-control js-assiduite-date-debut"
        value="{{ $dateDebutValeur }}"
        min="{{ $anneeFormulaire?->date_debut?->format('Y-m-d') }}"
        max="{{ $anneeFormulaire?->date_fin?->format('Y-m-d') }}"
        required
    >
</div>

<div class="form-group" data-assiduite-absence-only>
    <label class="form-label">Date fin</label>
    <input
        type="date"
        name="date_fin"
        class="form-control js-assiduite-date-fin"
        value="{{ $dateFinValeur }}"
        min="{{ $anneeFormulaire?->date_debut?->format('Y-m-d') }}"
        max="{{ $anneeFormulaire?->date_fin?->format('Y-m-d') }}"
    >
</div>

<div class="form-group">
    <label class="form-label">Période</label>
    <select name="periode" class="form-control">
        <option value="journee" @selected(old('periode', $evenement?->periode) === 'journee')>Journée entière</option>
        <option value="matin" @selected(old('periode', $evenement?->periode) === 'matin')>Matin</option>
        <option value="apres_midi" @selected(old('periode', $evenement?->periode) === 'apres_midi')>Après-midi</option>
        <option value="cours" @selected(old('periode', $evenement?->periode) === 'cours')>Cours</option>
    </select>
</div>

<div class="form-group">
    <label class="form-label">Heure prévue / début du cours</label>
    <input type="time" name="heure_debut" class="form-control js-assiduite-heure-debut" value="{{ old('heure_debut', $evenement?->heure_debut?->format('H:i')) }}">
</div>

<div class="form-group" data-assiduite-absence-only>
    <label class="form-label">Heure fin du cours</label>
    <input type="time" name="heure_fin" class="form-control" value="{{ old('heure_fin', $evenement?->heure_fin?->format('H:i')) }}">
</div>

<div class="form-group" data-assiduite-retard-only>
    <label class="form-label">Heure d’arrivée pour un retard</label>
    <input type="time" name="heure_arrivee" class="form-control js-assiduite-heure-arrivee" value="{{ old('heure_arrivee', $evenement?->heure_arrivee?->format('H:i')) }}">
</div>

<div class="form-group" data-assiduite-retard-only>
    <label class="form-label">Durée du retard en minutes</label>
    <input type="number" name="duree_minutes" class="form-control js-assiduite-duree" min="1" max="1440" value="{{ old('duree_minutes', $evenement?->duree_minutes) }}">
</div>

<div class="form-group">
    <label class="form-label">Catégorie du motif</label>
    <select name="categorie_motif" class="form-control">
        @foreach (['maladie', 'familial', 'transport', 'administratif', 'discipline', 'non_renseigne', 'autre'] as $categorie)
            <option value="{{ $categorie }}" @selected(old('categorie_motif', $evenement?->categorie_motif ?? 'non_renseigne') === $categorie)>
                {{ ucfirst(str_replace('_', ' ', $categorie)) }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label class="form-label">Motif</label>
    <textarea name="motif" class="form-control" rows="3">{{ old('motif', $evenement?->motif) }}</textarea>
</div>

@if (auth()->user()->estGestionnaire())
    <div class="form-group">
        <label class="form-label">Statut</label>
        <select name="statut" class="form-control">
            <option value="en_attente" @selected(old('statut', $evenement?->statut ?? 'en_attente') === 'en_attente')>En attente</option>
            <option value="justifiee" @selected(old('statut', $evenement?->statut) === 'justifiee')>Justifiée</option>
            <option value="non_justifiee" @selected(old('statut', $evenement?->statut) === 'non_justifiee')>Non justifiée</option>
            <option value="refusee" @selected(old('statut', $evenement?->statut) === 'refusee')>Refusée</option>
        </select>
    </div>
@else
    <input type="hidden" name="statut" value="en_attente">
@endif

<div class="form-group">
    <label class="form-label">Justification</label>
    <textarea name="justification" class="form-control" rows="3">{{ old('justification', $evenement?->justification) }}</textarea>
</div>

<div class="form-group">
    <label class="form-label">Pièce justificative</label>
    <input type="file" name="piece_justificative" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
</div>

@if (auth()->user()->estGestionnaire())
    <div class="form-group">
        <label class="form-label">Commentaire interne</label>
        <textarea name="commentaire_interne" class="form-control" rows="3">{{ old('commentaire_interne', $evenement?->commentaire_interne) }}</textarea>
    </div>
@endif

<div class="form-group">
    <input type="hidden" name="visible_parent" value="0">
    <label>
        <input type="checkbox" name="visible_parent" value="1" @checked(old('visible_parent', $evenement?->visible_parent ?? true))>
        Visible par le parent
    </label>
</div>
