<x-app-layout>
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Communication</div>
                <h1>Annonces de l’école</h1>
                <p>
                    Créez les annonces officielles. À la publication, le contenu complet est envoyé par email
                    aux destinataires et une notification est créée dans leur espace.
                </p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('annonces.create') }}" class="btn btn-primary">Nouvelle annonce</a>
            </div>
        </div>

        <div class="card">
            <form method="GET" action="{{ route('annonces.index') }}" class="filter-form">
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

                <button type="submit" class="btn">Filtrer</button>
                <a href="{{ route('annonces.index') }}" class="btn">Réinitialiser</a>
            </form>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table">
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
                                    <small>{{ $annonce->libellePriorite() }}</small>
                                </td>
                                <td>{{ $annonce->libelleType() }}</td>
                                <td>{{ $annonce->libelleCible() }}</td>
                                <td>
                                    <span class="badge {{ $annonce->est_publiee ? 'badge-success' : 'badge-warning' }}">
                                        {{ $annonce->est_publiee ? 'Publiée' : 'Brouillon' }}
                                    </span>
                                </td>
                                <td>{{ $annonce->notifications_count }}</td>
                                <td>
                                    {{ $annonce->date_publication?->format('d/m/Y H:i') ?? $annonce->created_at?->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <div class="request-row-actions">
                                        <a href="{{ route('annonces.show', $annonce) }}" class="btn">Voir</a>
                                        <a href="{{ route('annonces.edit', $annonce) }}" class="btn">Modifier</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">Aucune annonce enregistrée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $annonces->links() }}
        </div>
    </div>
</x-app-layout>
