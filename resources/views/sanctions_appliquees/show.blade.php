<x-app-layout>
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Sanction {{ $sanctionAppliquee->origine }}</div>
                <h1>{{ $sanctionAppliquee->sanction?->nom ?? 'Sanction' }}</h1>
                <p>{{ $sanctionAppliquee->inscription?->eleve?->nom }} {{ $sanctionAppliquee->inscription?->eleve?->prenom }} — {{ $sanctionAppliquee->inscription?->classe?->nom }}</p>
            </div>
            <div class="detail-actions">
                <a href="{{ route('sanctions-appliquees.index', ['annee_scolaire_id' => $sanctionAppliquee->inscription?->annee_scolaire_id, 'classe_id' => $sanctionAppliquee->inscription?->classe_id]) }}" class="btn">Retour</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        @endif

        @if ($sanctionAppliquee->statut === 'appliquee')
            <div class="alert alert-info">Cette sanction est appliquée et visible comme sanction en cours, mais ses effets définitifs ne sont pas encore comptés. Les points en moins seront pris en compte seulement après le passage au statut « Terminée ».</div>
        @elseif ($sanctionAppliquee->statut === 'terminee')
            <div class="alert alert-success">Cette sanction est terminée : elle est verrouillée et ses effets sont actifs.</div>
        @endif

        <div class="card">
            <div class="profile-grid">
                <div class="profile-row"><span>Année</span><strong>{{ $sanctionAppliquee->inscription?->anneeScolaire?->libelle }}</strong></div>
                <div class="profile-row"><span>Origine</span><strong>{{ ucfirst($sanctionAppliquee->origine) }}</strong></div>
                @php
                    $statutLibelle = match ($sanctionAppliquee->statut) {
                        'appliquee' => 'Appliquée - en cours, effet non définitif',
                        'terminee' => 'Terminée - effet actif et verrouillé',
                        'ignoree' => 'Ignorée - aucun effet',
                        'annulee' => 'Annulée - aucun effet',
                        default => 'Proposée - aucun effet',
                    };
                @endphp
                <div class="profile-row"><span>Statut</span><strong>{{ $statutLibelle }}</strong></div>
                <div class="profile-row"><span>Date application</span><strong>{{ $sanctionAppliquee->date_application?->format('d/m/Y') ?? '-' }}</strong></div>
                <div class="profile-row"><span>Période</span><strong>
                    @if ($sanctionAppliquee->periode_debut && $sanctionAppliquee->periode_fin)
                        {{ $sanctionAppliquee->periode_debut->format('d/m/Y') }} au {{ $sanctionAppliquee->periode_fin->format('d/m/Y') }}
                    @elseif ($sanctionAppliquee->trimestre)
                        {{ $sanctionAppliquee->trimestre->nom }}
                    @else
                        -
                    @endif
                </strong></div>
                <div class="profile-row"><span>Nombre d’événements</span><strong>{{ $sanctionAppliquee->nombre_evenements }}</strong></div>
                <div class="profile-row"><span>Trimestre</span><strong>{{ $sanctionAppliquee->trimestre?->nom ?? '-' }}</strong></div>
                <div class="profile-row"><span>Effet</span><strong>{{ ucfirst(str_replace('_', ' ', $sanctionAppliquee->type_effet)) }}</strong></div>
                <div class="profile-row"><span>Valeur</span><strong>{{ $sanctionAppliquee->valeur_effet ?? '-' }}</strong></div>
                <div class="profile-row"><span>Visible parent</span><strong>{{ $sanctionAppliquee->visible_parent ? 'Oui' : 'Non' }}</strong></div>
                <div class="profile-row"><span>Appliquée par</span><strong>{{ $sanctionAppliquee->appliquePar?->name ?? '-' }}</strong></div>
                <div class="profile-row"><span>Décision par</span><strong>{{ $sanctionAppliquee->decisionPar?->name ?? '-' }}</strong></div>
            </div>

            <div class="detail-text-grid">
                <section class="detail-text-section {{ auth()->user()->estGestionnaire() ? '' : 'detail-text-section-wide' }}">
                    <h2>Motif</h2>
                    <p>{{ $sanctionAppliquee->motif ?: 'Non renseigné' }}</p>
                </section>

                @if (auth()->user()->estGestionnaire())
                    <section class="detail-text-section">
                        <h2>Commentaire interne</h2>
                        <p>{{ $sanctionAppliquee->commentaire_interne ?: 'Aucun commentaire interne.' }}</p>
                    </section>
                @endif
            </div>

            @if (auth()->user()->estGestionnaire() && in_array($sanctionAppliquee->statut, ['proposee', 'appliquee'], true))
                <div class="detail-footer-actions">
                    @if ($sanctionAppliquee->statut === 'proposee')
                        <form action="{{ route('sanctions-appliquees.appliquer', $sanctionAppliquee) }}" method="POST" data-confirm="Appliquer cette sanction proposée ?" data-confirm-title="Application d’une sanction" data-confirm-button="Appliquer">
                            @csrf
                            <button type="submit" class="btn btn-primary">Appliquer</button>
                        </form>
                        <form action="{{ route('sanctions-appliquees.ignorer', $sanctionAppliquee) }}" method="POST" data-confirm="Ignorer cette proposition ?" data-confirm-title="Ignorer une proposition" data-confirm-button="Ignorer">
                            @csrf
                            <button type="submit" class="btn">Ignorer</button>
                        </form>
                    @elseif ($sanctionAppliquee->statut === 'appliquee')
                        <form action="{{ route('sanctions-appliquees.annuler', $sanctionAppliquee) }}" method="POST" data-confirm="Annuler cette sanction ?" data-confirm-title="Annulation d’une sanction" data-confirm-button="Annuler">
                            @csrf
                            <button type="submit" class="btn btn-danger">Annuler</button>
                        </form>
                        <form action="{{ route('sanctions-appliquees.terminer', $sanctionAppliquee) }}" method="POST" data-confirm="Terminer cette sanction ?" data-confirm-title="Fin d’une sanction" data-confirm-button="Terminer">
                            @csrf
                            <button type="submit" class="btn btn-primary">Terminer</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
