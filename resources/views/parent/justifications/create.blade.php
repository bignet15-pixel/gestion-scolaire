<x-app-layout>
    <div class="container request-detail-page">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="detail-header-card request-detail-hero">
            <div>
                <div class="detail-kicker">Espace parent</div>
                <h1>Justifier une absence ou un retard</h1>
                <p>La justification sera envoyée à l’école pour validation.</p>
            </div>
            <a href="{{ route('parent.eleves.show', $absence_retard->inscription->eleve) }}" class="btn">Retour à l’enfant</a>
        </div>

        <div class="request-detail-grid request-detail-grid-single">
            <div class="card request-detail-main">
                <div class="request-detail-titlebar">
                    <h2>{{ $absence_retard->libelleType() }} du {{ $absence_retard->date_debut?->format('d/m/Y') }}</h2>
                    <span class="status-pill status-{{ $absence_retard->statut }}">{{ $absence_retard->libelleStatut() }}</span>
                </div>

                <div class="info-grid request-info-grid">
                    <div>
                        <strong>Élève</strong>
                        <p>{{ $absence_retard->inscription?->eleve?->nom }} {{ $absence_retard->inscription?->eleve?->prenom }}</p>
                        <small>{{ $absence_retard->inscription?->eleve?->matricule ?? '-' }}</small>
                    </div>
                    <div>
                        <strong>Classe</strong>
                        <p>{{ $absence_retard->inscription?->classe?->nom ?? '-' }}</p>
                        <small>{{ $absence_retard->inscription?->classe?->anneeScolaire?->libelle ?? '-' }}</small>
                    </div>
                    <div>
                        <strong>Période</strong>
                        <p>{{ $absence_retard->libellePeriode() }}</p>
                    </div>
                    <div>
                        <strong>Motif école</strong>
                        <p>{{ $absence_retard->motif ?? '-' }}</p>
                    </div>
                </div>

                <form action="{{ route('parent.justifications.store', $absence_retard) }}" method="POST" enctype="multipart/form-data" class="parent-justification-form-page">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Motif court</label>
                        <input type="text" name="motif" value="{{ old('motif') }}" class="form-control" placeholder="Ex : maladie, urgence familiale..." required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Explication</label>
                        <textarea name="message" class="form-control" rows="5" placeholder="Explique rapidement la situation">{{ old('message') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pièce justificative</label>
                        <input type="file" name="piece_jointe" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        <small class="muted-text">Formats acceptés : PDF, JPG, PNG, WEBP. Taille maximale : 5 Mo.</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Envoyer la justification</button>
                        <a href="{{ route('parent.eleves.show', $absence_retard->inscription->eleve) }}" class="btn">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
