<x-app-layout>
{{-- Vue Blade : resources/views/paiements/show.blade.php --}}
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Détail paiement</div>

                <h1>{{ $paiement->numero_paiement }}</h1>

                <p>
                    Reçu de paiement, informations de l’élève, inscription,
                    gestionnaire et situation financière.
                </p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('paiements.index') }}" class="btn">
                    Retour
                </a>

                <a href="{{ route('paiements.recu', $paiement) }}" class="btn btn-success">
                    Télécharger reçu PDF
                </a>

                <a href="{{ route('paiements.edit', $paiement) }}" class="btn btn-primary">
                    Modifier
                </a>
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-info-card">
                <div class="detail-label">Montant payé</div>
                <div class="detail-value">
                    {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA
                </div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Date paiement</div>
                <div class="detail-value">
                    {{ $paiement->date_paiement?->format('d/m/Y') ?? '-' }}
                </div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Mode</div>
                <div class="detail-value">
                    {{ $paiement->mode_paiement }}
                </div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Gestionnaire</div>
                <div class="detail-value">
                    {{ $paiement->gestionnaire?->name ?? '-' }}
                </div>
            </div>
        </div>

        <div class="student-profile-card">
            <div class="student-photo-box">
                {{-- Condition : $paiement->inscription?->eleve?->photo. --}}
                @if ($paiement->inscription?->eleve?->photo)
                    <img src="{{ asset('storage/' . $paiement->inscription->eleve->photo) }}" alt="Photo élève">
                {{-- Sinon, affichage de l alternative prevue. --}}
                @else
                    <div class="student-photo-placeholder">
                        {{ strtoupper(substr($paiement->inscription?->eleve?->nom ?? 'E', 0, 1)) }}{{ strtoupper(substr($paiement->inscription?->eleve?->prenom ?? 'L', 0, 1)) }}
                    </div>
                @endif
            </div>

            <div class="student-info">
                <div class="student-name">
                    {{ $paiement->inscription?->eleve?->nom }}
                    {{ $paiement->inscription?->eleve?->prenom }}
                </div>

                <div class="student-matricule">
                    Matricule : {{ $paiement->inscription?->eleve?->matricule ?? '-' }}
                </div>

                <div class="profile-grid">
                    <div class="profile-row">
                        <span>Classe</span>
                        <strong>{{ $paiement->inscription?->classe?->nom ?? '-' }}</strong>
                    </div>

                    <div class="profile-row">
                        <span>Année scolaire</span>
                        <strong>{{ $paiement->inscription?->anneeScolaire?->libelle ?? '-' }}</strong>
                    </div>

                    <div class="profile-row">
                        <span>Contact parent</span>
                        <strong>{{ $paiement->contact_parent ?? '-' }}</strong>
                    </div>

                    <div class="profile-row">
                        <span>Contact gestionnaire</span>
                        <strong>{{ $paiement->contact_gestionnaire ?? '-' }}</strong>
                    </div>
                </div>

                <p style="margin-top: 16px;">
                    <a href="{{ route('eleves.show', $paiement->inscription->eleve) }}" class="btn btn-success">
                        Voir fiche élève
                    </a>

                    <a href="{{ route('inscriptions.show', $paiement->inscription) }}" class="btn btn-primary">
                        Voir inscription
                    </a>
                </p>
            </div>
        </div>

        <div class="card inscription-finance-card">
            <h2>Situation financière après paiement</h2>

            <div class="finance-grid">
                <div class="finance-item">
                    <div class="finance-label">Frais attendus</div>
                    <div class="finance-value">
                        {{ number_format($paiement->inscription->frais_attendu, 0, ',', ' ') }} FCFA
                    </div>
                </div>

                <div class="finance-item success">
                    <div class="finance-label">Total payé</div>
                    <div class="finance-value">
                        {{ number_format($paiement->inscription->totalPaye(), 0, ',', ' ') }} FCFA
                    </div>
                </div>

                <div class="finance-item danger">
                    <div class="finance-label">Reste à payer</div>
                    <div class="finance-value">
                        {{ number_format($paiement->inscription->resteAPayer(), 0, ',', ' ') }} FCFA
                    </div>
                </div>

                <div class="finance-item warning">
                    <div class="finance-label">Paiement actuel</div>
                    <div class="finance-value">
                        {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>