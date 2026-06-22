<x-app-layout>
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Demandes parentales</div>
                <h1>Paiements déclarés</h1>
                <p>Validation des paiements déclarés par les parents. Un paiement officiel est créé seulement après validation.</p>
            </div>
        </div>

        <div class="card">
            <form method="GET" action="{{ route('gestionnaire.paiements-declares.index') }}" class="filter-form filter-form-large">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        <option value="">Toutes</option>
                        @foreach ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected((string) $selectedAnneeId === (string) $annee->id)>
                                {{ $annee->libelle }}
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

                <div class="form-group">
                    <label class="form-label">Recherche élève</label>
                    <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Nom, prénom, matricule...">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="{{ route('gestionnaire.paiements-declares.index') }}" class="btn">Réinitialiser</a>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Déclarations</h2>
            <div class="table-responsive">
                <table class="table requests-table requests-table-clean">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Élève</th>
                            <th>Parent</th>
                            <th>Montant</th>
                            <th>Mode</th>
                            <th>Référence</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paiementsDeclares as $paiementDeclare)
                            <tr>
                                <td>{{ $paiementDeclare->created_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    {{ $paiementDeclare->inscription?->eleve?->nom }} {{ $paiementDeclare->inscription?->eleve?->prenom }}
                                    <br>
                                    <small>{{ $paiementDeclare->inscription?->classe?->nom ?? '-' }} / {{ $paiementDeclare->inscription?->anneeScolaire?->libelle ?? '-' }}</small>
                                </td>
                                <td>{{ $paiementDeclare->parent?->nom }} {{ $paiementDeclare->parent?->prenom }}</td>
                                <td>{{ number_format($paiementDeclare->montant, 0, ',', ' ') }} FCFA</td>
                                <td>{{ str_replace('_', ' ', $paiementDeclare->mode_paiement) }}</td>
                                <td>{{ $paiementDeclare->reference_transaction ?? '-' }}</td>
                                <td><span class="status-pill status-{{ $paiementDeclare->statut }}">{{ $paiementDeclare->libelleStatut() }}</span></td>
                                <td>
                                    <a href="{{ route('gestionnaire.paiements-declares.show', $paiementDeclare) }}" class="btn btn-primary btn-sm">
                                        Voir / traiter
                                    </a>
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
