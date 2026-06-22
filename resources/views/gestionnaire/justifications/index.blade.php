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
                <h1>Justifications d’absences et retards</h1>
                <p>Accepter ou refuser les demandes de justification envoyées par les parents.</p>
            </div>
        </div>

        <div class="card">
            <form method="GET" action="{{ route('gestionnaire.justifications-parent.index') }}" class="filter-form filter-form-large">
                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-control">
                        <option value="">Tous</option>
                        <option value="en_attente" @selected($selectedStatut === 'en_attente')>En attente</option>
                        <option value="acceptee" @selected($selectedStatut === 'acceptee')>Acceptée</option>
                        <option value="refusee" @selected($selectedStatut === 'refusee')>Refusée</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Recherche élève</label>
                    <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Nom, prénom, matricule...">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="{{ route('gestionnaire.justifications-parent.index') }}" class="btn">Réinitialiser</a>
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
                            <th>Événement</th>
                            <th>Parent</th>
                            <th>Motif parent</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($justifications as $justification)
                            <tr>
                                <td>{{ $justification->created_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    {{ $justification->absenceRetard?->inscription?->eleve?->nom }}
                                    {{ $justification->absenceRetard?->inscription?->eleve?->prenom }}
                                    <br>
                                    <small>{{ $justification->absenceRetard?->inscription?->classe?->nom ?? '-' }}</small>
                                </td>
                                <td>
                                    {{ $justification->absenceRetard?->libelleType() }} du
                                    {{ $justification->absenceRetard?->date_debut?->format('d/m/Y') }}
                                    <br>
                                    <small>{{ $justification->absenceRetard?->libelleStatut() }}</small>
                                </td>
                                <td>{{ $justification->parent?->nom }} {{ $justification->parent?->prenom }}</td>
                                <td>
                                    <strong>{{ $justification->motif }}</strong>
                                    <br>
                                    <small>{{ $justification->message ?? '-' }}</small>
                                </td>
                                <td><span class="status-pill status-{{ $justification->statut }}">{{ $justification->libelleStatut() }}</span></td>
                                <td>
                                    <a href="{{ route('gestionnaire.justifications-parent.show', $justification) }}" class="btn btn-primary btn-sm">
                                        Voir / traiter
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">Aucune demande de justification.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
