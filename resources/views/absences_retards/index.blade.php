<x-app-layout>
    <div class="container">
        <div class="card">
            <h1>Absences et retards</h1>

            @php
                $selectedAnnee = $selectedAnneeId
                    ? $annees->first(fn ($annee) => (string) $annee->id === (string) $selectedAnneeId)
                    : null;
            @endphp

            <form action="{{ route('absences-retards.index') }}" method="GET" class="filter-form filter-form-large">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        @foreach ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected((string) $selectedAnneeId === (string) $annee->id)>
                                {{ $annee->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Classe</label>
                    <select name="classe_id" class="form-control">
                        <option value="">Toutes les classes</option>
                        @foreach ($classes as $classe)
                            <option value="{{ $classe->id }}" @selected((string) $selectedClasseId === (string) $classe->id)>
                                {{ $classe->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control">
                        <option value="">Tous les types</option>
                        <option value="absence" @selected($selectedType === 'absence')>Absence</option>
                        <option value="retard" @selected($selectedType === 'retard')>Retard</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-control">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" @selected($selectedStatut === 'en_attente')>En attente</option>
                        <option value="justifiee" @selected($selectedStatut === 'justifiee')>Justifiée</option>
                        <option value="non_justifiee" @selected($selectedStatut === 'non_justifiee')>Non justifiée</option>
                        <option value="refusee" @selected($selectedStatut === 'refusee')>Refusée</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Du</label>
                    <input type="date" name="date_debut" class="form-control" value="{{ $dateDebut }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Au</label>
                    <input type="date" name="date_fin" class="form-control" value="{{ $dateFin }}">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="{{ route('absences-retards.index') }}" class="btn">Réinitialiser</a>
                </div>
            </form>

            @if (! $selectedAnnee?->estFermee())
                <p>
                    <a
                        href="{{ route('absences-retards.create', [
                            'annee_scolaire_id' => $selectedAnneeId,
                            'classe_id' => $selectedClasseId,
                        ]) }}"
                        class="btn btn-primary"
                    >
                        Enregistrer une absence ou un retard
                    </a>
                </p>
            @endif

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="dashboard-grid assiduite-summary">
                <div class="stat-card">
                    <div class="stat-title">Absences</div>
                    <div class="stat-value">{{ $statistiques['absences'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Retards</div>
                    <div class="stat-value">{{ $statistiques['retards'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">En attente</div>
                    <div class="stat-value">{{ $statistiques['en_attente'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Non justifiées / refusées</div>
                    <div class="stat-value">{{ $statistiques['non_justifiees'] }}</div>
                </div>
            </div>

            <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Élève</th>
                        <th>Classe</th>
                        <th>Type</th>
                        <th>Période</th>
                        <th>Durée</th>
                        <th>Statut</th>
                        <th>Visible parent</th>
                        <th>Enregistré par</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($evenements as $evenement)
                        @php
                            $anneeFermee = $evenement->inscription?->anneeScolaire?->estFermee();
                            $typeClass = $evenement->type === 'absence' ? 'badge-primary-soft' : 'badge-warning';
                            $statutClass = match ($evenement->statut) {
                                'justifiee' => 'badge-success',
                                'non_justifiee', 'refusee' => 'badge-danger',
                                default => 'badge-warning',
                            };
                        @endphp
                        <tr>
                            <td>
                                {{ $evenement->date_debut?->format('d/m/Y') }}
                                @if ($evenement->date_fin && ! $evenement->date_fin->equalTo($evenement->date_debut))
                                    au {{ $evenement->date_fin->format('d/m/Y') }}
                                @endif
                            </td>
                            <td>
                                {{ $evenement->inscription?->eleve?->nom }}
                                {{ $evenement->inscription?->eleve?->prenom }}
                            </td>
                            <td>{{ $evenement->inscription?->classe?->nom ?? '-' }}</td>
                            <td><span class="badge {{ $typeClass }}">{{ $evenement->libelleType() }}</span></td>
                            <td>{{ $evenement->libellePeriode() }}</td>
                            <td>{{ $evenement->duree_minutes ? $evenement->duree_minutes . ' min' : '-' }}</td>
                            <td><span class="badge {{ $statutClass }}">{{ $evenement->libelleStatut() }}</span></td>
                            <td>
                                <span class="badge {{ $evenement->visible_parent ? 'badge-success' : 'badge-muted' }}">
                                    {{ $evenement->visible_parent ? 'Oui' : 'Non' }}
                                </span>
                            </td>
                            <td>{{ $evenement->enregistrePar?->name ?? '-' }}</td>
                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('absences-retards.show', $evenement) }}" class="btn btn-success">Détail</a>

                                    @if (auth()->user()->estGestionnaire() && ! $anneeFermee)
                                        <a href="{{ route('absences-retards.edit', $evenement) }}" class="btn btn-primary">Modifier</a>

                                        <form
                                            action="{{ route('absences-retards.destroy', $evenement) }}"
                                            method="POST"
                                            data-confirm="Voulez-vous vraiment supprimer cet événement d’assiduité ?"
                                            data-confirm-title="Suppression d’un événement"
                                            data-confirm-button="Supprimer"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">Supprimer</button>
                                        </form>
                                    @elseif ($anneeFermee)
                                        <span class="badge">Historique</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10">Aucune absence ou retard ne correspond aux filtres sélectionnés.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</x-app-layout>
