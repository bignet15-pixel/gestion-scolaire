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
                <h1>Réinscriptions</h1>
                <p>Validation des demandes de passage ou de redoublement faites par les parents.</p>
            </div>
        </div>

        <div class="card">
            <form method="GET" action="{{ route('gestionnaire.demandes-reinscription.index') }}" class="filter-form filter-form-large">
                <div class="form-group">
                    <label class="form-label">Nouvelle année</label>
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
                        <option value="validee" @selected($selectedStatut === 'validee')>Validée</option>
                        <option value="refusee" @selected($selectedStatut === 'refusee')>Refusée</option>
                        <option value="annulee" @selected($selectedStatut === 'annulee')>Annulée</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Recherche élève</label>
                    <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Nom, prénom, matricule...">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="{{ route('gestionnaire.demandes-reinscription.index') }}" class="btn">Réinitialiser</a>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Demandes</h2>
            <div class="table-responsive">
                <table class="table requests-table requests-table-clean">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Élève</th>
                            <th>Ancienne classe</th>
                            <th>Classe demandée</th>
                            <th>Décision système</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($demandes as $demande)
                            <tr>
                                <td>{{ $demande->created_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    {{ $demande->eleve?->nom }} {{ $demande->eleve?->prenom }}
                                    <br>
                                    <small>Parent : {{ $demande->parent?->nom }} {{ $demande->parent?->prenom }}</small>
                                </td>
                                <td>
                                    {{ $demande->ancienneClasse?->nom ?? '-' }}
                                    <br>
                                    <small>{{ $demande->ancienneInscription?->anneeScolaire?->libelle ?? '-' }}</small>
                                </td>
                                <td>
                                    {{ $demande->classeDemandee?->nom ?? '-' }}
                                    <br>
                                    <small>{{ $demande->nouvelleAnneeScolaire?->libelle ?? '-' }}</small>
                                </td>
                                <td>{{ $demande->libelleDecisionSysteme() }}</td>
                                <td><span class="status-pill status-{{ $demande->statut }}">{{ $demande->libelleStatut() }}</span></td>
                                <td>
                                    <a href="{{ route('gestionnaire.demandes-reinscription.show', $demande) }}" class="btn btn-primary btn-sm">
                                        Voir / traiter
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">Aucune demande de réinscription.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
