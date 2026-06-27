<x-app-layout>
    <div class="container communication-page">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="detail-header-card communication-hero">
            <div>
                <div class="detail-kicker">Communication</div>
                <h1>Annonces de l’école</h1>
                <p>
                    Créez et publiez les annonces officielles. Les destinataires reçoivent une notification
                    dans leur espace et les emails sont placés dans la file d’attente.
                </p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('annonces.create') }}" class="btn btn-primary">Nouvelle annonce</a>
            </div>
        </div>

        <div class="card communication-filter-card">
            <form method="GET" action="{{ route('annonces.index') }}" class="filter-form communication-filter-form">
                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select name="statut" id="statut" class="form-control">
                        <option value="">Tous</option>
                        <option value="publiee" @selected(request('statut') === 'publiee')>Publiées</option>
                        <option value="brouillon" @selected(request('statut') === 'brouillon')>Brouillons</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cible">Cible</label>
                    <select name="cible" id="cible" class="form-control">
                        <option value="">Toutes</option>
                        @foreach ($cibles as $value => $label)
                            <option value="{{ $value }}" @selected(request('cible') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions communication-filter-actions">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="{{ route('annonces.index') }}" class="btn">Réinitialiser</a>
                </div>
            </form>
        </div>

        <div class="card communication-table-card">
            <div class="communication-card-title">
                <div>
                    <h2>Liste des annonces</h2>
                    <p>Suivi des brouillons, publications et notifications créées.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table communication-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Cible</th>
                            <th>Statut</th>
                            <th>Notifications</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($annonces as $annonce)
                            <tr>
                                <td>
                                    <strong>{{ $annonce->titre }}</strong>
                                    <br>
                                    <span class="communication-muted">{{ $annonce->libellePriorite() }}</span>
                                </td>
                                <td>{{ $annonce->libelleType() }}</td>
                                <td>{{ $annonce->libelleCible() }}</td>
                                <td>
                                    <span class="badge {{ $annonce->est_publiee ? 'badge-success' : 'badge-warning' }}">
                                        {{ $annonce->est_publiee ? 'Publiée' : 'Brouillon' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="communication-count">{{ $annonce->notifications_count }}</span>
                                </td>
                                <td>
                                    {{ $annonce->date_publication?->format('d/m/Y H:i') ?? $annonce->created_at?->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('annonces.show', $annonce) }}" class="btn btn-primary">Voir</a>
                                        <a href="{{ route('annonces.edit', $annonce) }}" class="btn">Modifier</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">Aucune annonce enregistrée.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $annonces->links() }}
        </div>
    </div>
</x-app-layout>
