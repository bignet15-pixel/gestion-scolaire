<x-app-layout>
    <div class="container">
        <div class="card">
            <h1>Sanctions appliquées et proposées</h1>

            @php
                $selectedAnnee = $annees->first(
                    fn ($annee) => (string) $annee->id === (string) $selectedAnneeId
                );
            @endphp

            <form action="{{ route('sanctions-appliquees.index') }}" method="GET" class="filter-form filter-form-large">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        @foreach ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected((string) $selectedAnneeId === (string) $annee->id)>{{ $annee->libelle }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Classe</label>
                    <select name="classe_id" class="form-control">
                        <option value="">Toutes les classes</option>
                        @foreach ($classes as $classe)
                            <option value="{{ $classe->id }}" @selected((string) $selectedClasseId === (string) $classe->id)>{{ $classe->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-control">
                        <option value="">Tous les statuts</option>
                        @foreach (['proposee', 'appliquee', 'ignoree', 'annulee', 'terminee'] as $statut)
                            <option value="{{ $statut }}" @selected($selectedStatut === $statut)>{{ ucfirst($statut) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Origine</label>
                    <select name="origine" class="form-control">
                        <option value="">Toutes les origines</option>
                        <option value="automatique" @selected($selectedOrigine === 'automatique')>Automatique</option>
                        <option value="manuel" @selected($selectedOrigine === 'manuel')>Manuelle</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="{{ route('sanctions-appliquees.index') }}" class="btn">Réinitialiser</a>
                </div>
            </form>

            @if (auth()->user()->estGestionnaire() && ! $selectedAnnee?->estFermee())
                <p>
                    <a href="{{ route('sanctions-appliquees.create', ['annee_scolaire_id' => $selectedAnneeId, 'classe_id' => $selectedClasseId]) }}" class="btn btn-primary">
                        Appliquer une sanction manuelle
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
                    <div class="stat-title">Proposées</div>
                    <div class="stat-value">{{ $statistiques['proposees'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Appliquées</div>
                    <div class="stat-value">{{ $statistiques['appliquees'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Ignorées</div>
                    <div class="stat-value">{{ $statistiques['ignorees'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Annulées / terminées</div>
                    <div class="stat-value">{{ $statistiques['cloturees'] }}</div>
                </div>
            </div>

            <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Classe</th>
                        <th>Sanction</th>
                        <th>Origine</th>
                        <th>Période</th>
                        <th>Événements</th>
                        <th>Effet</th>
                        <th>Valeur</th>
                        <th>Statut</th>
                        <th>Visible parent</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sanctionsAppliquees as $element)
                        @php
                            $statutClass = match ($element->statut) {
                                'terminee' => 'badge-success',
                                'appliquee' => 'badge-primary-soft',
                                'ignoree', 'annulee' => 'badge-muted',
                                default => 'badge-warning',
                            };
                            $statutLibelle = match ($element->statut) {
                                'appliquee' => 'Appliquée - en cours',
                                'terminee' => 'Terminée - effet actif',
                                'ignoree' => 'Ignorée',
                                'annulee' => 'Annulée',
                                default => 'Proposée',
                            };
                            $origineClass = $element->origine === 'automatique' ? 'badge-primary-soft' : 'badge-muted';
                            $effetClass = match ($element->type_effet) {
                                'points_en_moins' => 'badge-danger',
                                'convocation_administration', 'avertissement' => 'badge-warning',
                                'appel_parent' => 'badge-primary-soft',
                                default => 'badge-muted',
                            };
                        @endphp
                        <tr>
                            <td>{{ $element->inscription?->eleve?->nom }} {{ $element->inscription?->eleve?->prenom }}</td>
                            <td>{{ $element->inscription?->classe?->nom ?? '-' }}</td>
                            <td>{{ $element->sanction?->nom ?? '-' }}</td>
                            <td><span class="badge {{ $origineClass }}">{{ ucfirst($element->origine) }}</span></td>
                            <td>
                                {{ $element->periode_debut?->format('d/m/Y') ?? '-' }}
                                @if ($element->periode_fin)
                                    au {{ $element->periode_fin->format('d/m/Y') }}
                                @endif
                            </td>
                            <td>{{ $element->nombre_evenements }}</td>
                            <td><span class="badge {{ $effetClass }}">{{ ucfirst(str_replace('_', ' ', $element->type_effet)) }}</span></td>
                            <td>{{ $element->valeur_effet !== null ? number_format((float) $element->valeur_effet, 2, ',', ' ') : '-' }}</td>
                            <td><span class="badge {{ $statutClass }}">{{ $statutLibelle }}</span></td>
                            <td>
                                <span class="badge {{ $element->visible_parent ? 'badge-success' : 'badge-muted' }}">
                                    {{ $element->visible_parent ? 'Oui' : 'Non' }}
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('sanctions-appliquees.show', $element) }}" class="btn btn-success">Détail</a>

                                    @if (auth()->user()->estGestionnaire())
                                        @if ($element->statut === 'proposee')
                                            <form action="{{ route('sanctions-appliquees.appliquer', $element) }}" method="POST" data-confirm="Appliquer cette sanction proposée ?" data-confirm-title="Application d’une sanction" data-confirm-button="Appliquer">
                                                @csrf
                                                <button type="submit" class="btn btn-primary">Appliquer</button>
                                            </form>
                                            <form action="{{ route('sanctions-appliquees.ignorer', $element) }}" method="POST" data-confirm="Ignorer cette proposition de sanction ?" data-confirm-title="Ignorer une proposition" data-confirm-button="Ignorer">
                                                @csrf
                                                <button type="submit" class="btn">Ignorer</button>
                                            </form>
                                        @elseif ($element->statut === 'appliquee')
                                            <form action="{{ route('sanctions-appliquees.annuler', $element) }}" method="POST" data-confirm="Annuler cette sanction appliquée ?" data-confirm-title="Annulation d’une sanction" data-confirm-button="Annuler">
                                                @csrf
                                                <button type="submit" class="btn btn-danger">Annuler</button>
                                            </form>
                                            <form action="{{ route('sanctions-appliquees.terminer', $element) }}" method="POST" data-confirm="Marquer cette sanction comme terminée ?" data-confirm-title="Fin d’une sanction" data-confirm-button="Terminer">
                                                @csrf
                                                <button type="submit" class="btn btn-primary">Terminer</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11">Aucune sanction proposée ou appliquée ne correspond aux filtres sélectionnés.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</x-app-layout>
