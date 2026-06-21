<x-app-layout>
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Justification d’assiduité</div>
                <h1>{{ $evenement->libelleType() }} — {{ $evenement->inscription?->eleve?->nom }} {{ $evenement->inscription?->eleve?->prenom }}</h1>
                <p>{{ $evenement->inscription?->classe?->nom }} / {{ $evenement->inscription?->anneeScolaire?->libelle }}</p>
            </div>
            <div class="detail-actions">
                <a href="{{ route('absences-retards.show', $evenement) }}" class="btn">Retour au détail</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="card">
            <h2>Informations de l’événement</h2>
            <div class="profile-grid">
                <div class="profile-row"><span>Élève</span><strong>{{ $evenement->inscription?->eleve?->nom }} {{ $evenement->inscription?->eleve?->prenom }}</strong></div>
                <div class="profile-row"><span>Classe</span><strong>{{ $evenement->inscription?->classe?->nom ?? '-' }}</strong></div>
                <div class="profile-row"><span>Année scolaire</span><strong>{{ $evenement->inscription?->anneeScolaire?->libelle ?? '-' }}</strong></div>
                <div class="profile-row"><span>Type</span><strong>{{ $evenement->libelleType() }}</strong></div>
                <div class="profile-row"><span>Date début</span><strong>{{ $evenement->date_debut?->format('d/m/Y') }}</strong></div>
                <div class="profile-row"><span>Date fin</span><strong>{{ $evenement->date_fin?->format('d/m/Y') ?? '-' }}</strong></div>
                <div class="profile-row"><span>Période</span><strong>{{ $evenement->libellePeriode() }}</strong></div>
                <div class="profile-row"><span>Statut actuel</span><strong>{{ $evenement->libelleStatut() }}</strong></div>
                <div class="profile-row"><span>Enregistré par</span><strong>{{ $evenement->enregistrePar?->name ?? '-' }}</strong></div>
            </div>

            <div class="detail-text-grid">
                <section class="detail-text-section detail-text-section-wide">
                    <h2>Motif actuel</h2>
                    <p>{{ $evenement->motif ?: 'Non renseigné' }}</p>
                </section>
            </div>
        </div>

        <div class="card">
            <h2>Décision de justification</h2>

            <form action="{{ route('absences-retards.justifier.update', $evenement) }}" method="POST" enctype="multipart/form-data" class="form-grid">
                @csrf
                @method('PATCH')

                <div class="form-group">
                    <label for="statut" class="form-label">Statut</label>
                    <select id="statut" name="statut" class="form-control" required>
                        <option value="en_attente" @selected(old('statut', $evenement->statut) === 'en_attente')>En attente</option>
                        <option value="justifiee" @selected(old('statut', $evenement->statut) === 'justifiee')>Justifiée</option>
                        <option value="non_justifiee" @selected(old('statut', $evenement->statut) === 'non_justifiee')>Non justifiée</option>
                        <option value="refusee" @selected(old('statut', $evenement->statut) === 'refusee')>Refusée</option>
                    </select>
                </div>

                <div class="form-group form-group-full">
                    <label for="justification" class="form-label">Justification</label>
                    <textarea id="justification" name="justification" class="form-control" rows="4">{{ old('justification', $evenement->justification) }}</textarea>
                </div>

                <div class="form-group">
                    <label for="piece_justificative" class="form-label">Pièce justificative</label>
                    <input id="piece_justificative" type="file" name="piece_justificative" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    @if ($evenement->piece_justificative)
                        <small class="form-help">
                            Une pièce existe déjà. Elle sera conservée si aucun nouveau fichier n’est envoyé.
                            <a href="{{ asset('storage/' . $evenement->piece_justificative) }}" target="_blank">Voir la pièce actuelle</a>.
                        </small>
                    @else
                        <small class="form-help">Formats acceptés : PDF, JPG, JPEG, PNG. Taille maximale : 4 Mo.</small>
                    @endif
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="visible_parent" value="1" @checked(old('visible_parent', $evenement->visible_parent))>
                        Visible par le parent
                    </label>
                    <small class="form-help">Cette information sera utilisée plus tard dans l’application mobile parent.</small>
                </div>

                <div class="form-group form-group-full">
                    <label for="commentaire_interne" class="form-label">Commentaire interne</label>
                    <textarea id="commentaire_interne" name="commentaire_interne" class="form-control" rows="3">{{ old('commentaire_interne', $evenement->commentaire_interne) }}</textarea>
                    <small class="form-help">Visible uniquement côté gestionnaire.</small>
                </div>

                <div class="form-actions form-group-full">
                    <a href="{{ route('absences-retards.show', $evenement) }}" class="btn">Annuler</a>
                    <button type="submit" class="btn btn-primary">Enregistrer la justification</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
