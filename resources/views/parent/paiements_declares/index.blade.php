<x-app-layout>
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Espace parent</div>
                <h1>Paiements déclarés</h1>
                <p>Historique des paiements que vous avez déclarés à l’école.</p>
            </div>
            <div class="detail-actions">
                <a href="{{ route('dashboard') }}" class="btn">Retour</a>
            </div>
        </div>

        <div class="card">
            <form method="GET" action="{{ route('parent.paiements-declares.index') }}" class="filter-form filter-form-large">
                <div class="form-group">
                    <label class="form-label">Enfant</label>
                    <select name="eleve_id" class="form-control">
                        <option value="">Tous</option>
                        @foreach ($enfants as $enfant)
                            <option value="{{ $enfant->id }}" @selected((string) $selectedEnfantId === (string) $enfant->id)>
                                {{ $enfant->nom }} {{ $enfant->prenom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-control">
                        <option value="">Tous</option>
                        <option value="en_attente" @selected($selectedStatut === 'en_attente')>En attente</option>
                        <option value="valide" @selected($selectedStatut === 'valide')>Validé</option>
                        <option value="refuse" @selected($selectedStatut === 'refuse')>Refusé</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="{{ route('parent.paiements-declares.index') }}" class="btn">Réinitialiser</a>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Historique</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Élève</th>
                            <th>Classe</th>
                            <th>Montant</th>
                            <th>Mode</th>
                            <th>Référence</th>
                            <th>Statut</th>
                            <th>Preuve</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paiementsDeclares as $paiementDeclare)
                            <tr>
                                <td>{{ $paiementDeclare->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $paiementDeclare->inscription?->eleve?->nom }} {{ $paiementDeclare->inscription?->eleve?->prenom }}</td>
                                <td>
                                    {{ $paiementDeclare->inscription?->classe?->nom ?? '-' }}
                                    <br>
                                    <small>{{ $paiementDeclare->inscription?->anneeScolaire?->libelle ?? '-' }}</small>
                                </td>
                                <td>{{ number_format($paiementDeclare->montant, 0, ',', ' ') }} FCFA</td>
                                <td>{{ str_replace('_', ' ', $paiementDeclare->mode_paiement) }}</td>
                                <td>{{ $paiementDeclare->reference_transaction ?? '-' }}</td>
                                <td>{{ $paiementDeclare->libelleStatut() }}</td>
                                <td>
                                    @if ($paiementDeclare->preuve_paiement)
                                        <a href="{{ route('parent.paiements-declares.preuve', $paiementDeclare) }}" class="btn">Voir</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">Aucun paiement déclaré.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
