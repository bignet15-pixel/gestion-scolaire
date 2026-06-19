<x-app-layout>
    <div class="container">
        <div class="card">
            <h1>Configuration des sanctions</h1>

            <form action="{{ route('sanctions.index') }}" method="GET" class="filter-form">
                <div class="form-group">
                    <label class="form-label">Catégorie</label>
                    <select name="categorie" class="form-control">
                        <option value="">Toutes les catégories</option>
                        @foreach (['absence', 'retard', 'conduite'] as $categorie)
                            <option value="{{ $categorie }}" @selected($selectedCategorie === $categorie)>{{ ucfirst($categorie) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Mode</label>
                    <select name="mode_declenchement" class="form-control">
                        <option value="">Tous les modes</option>
                        @foreach (['automatique', 'manuel', 'mixte'] as $mode)
                            <option value="{{ $mode }}" @selected($selectedMode === $mode)>{{ ucfirst($mode) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">État</label>
                    <select name="active" class="form-control">
                        <option value="">Tous</option>
                        <option value="1" @selected((string) $selectedActive === '1')>Active</option>
                        <option value="0" @selected((string) $selectedActive === '0')>Désactivée</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="{{ route('sanctions.index') }}" class="btn">Réinitialiser</a>
                </div>
            </form>

            <p><a href="{{ route('sanctions.create') }}" class="btn btn-primary">Ajouter une sanction</a></p>

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

            <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Mode</th>
                        <th>Seuil</th>
                        <th>Période</th>
                        <th>Effet</th>
                        <th>Valeur effet</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sanctions as $sanction)
                        @php
                            $categorieClass = match ($sanction->categorie) {
                                'absence' => 'badge-primary-soft',
                                'retard' => 'badge-warning',
                                default => 'badge-danger',
                            };
                            $modeClass = match ($sanction->mode_declenchement) {
                                'automatique' => 'badge-primary-soft',
                                'mixte' => 'badge-warning',
                                default => 'badge-muted',
                            };
                            $effetClass = match ($sanction->type_effet) {
                                'points_en_moins' => 'badge-danger',
                                'convocation_administration', 'avertissement' => 'badge-warning',
                                'appel_parent' => 'badge-primary-soft',
                                default => 'badge-muted',
                            };
                        @endphp
                        <tr>
                            <td>{{ $sanction->nom }}</td>
                            <td><span class="badge {{ $categorieClass }}">{{ ucfirst($sanction->categorie) }}</span></td>
                            <td><span class="badge {{ $modeClass }}">{{ ucfirst($sanction->mode_declenchement) }}</span></td>
                            <td>{{ $sanction->seuil ?? '-' }}</td>
                            <td>{{ $sanction->periode_calcul ? ucfirst($sanction->periode_calcul) : '-' }}</td>
                            <td><span class="badge {{ $effetClass }}">{{ ucfirst(str_replace('_', ' ', $sanction->type_effet)) }}</span></td>
                            <td>{{ $sanction->valeur_effet !== null ? number_format((float) $sanction->valeur_effet, 2, ',', ' ') : '-' }}</td>
                            <td><span class="badge {{ $sanction->active ? 'badge-success' : 'badge-muted' }}">{{ $sanction->active ? 'Oui' : 'Non' }}</span></td>
                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('sanctions.show', $sanction) }}" class="btn btn-success">Détail</a>
                                    <a href="{{ route('sanctions.edit', $sanction) }}" class="btn btn-primary">Modifier</a>
                                    <form
                                        action="{{ route('sanctions.destroy', $sanction) }}"
                                        method="POST"
                                        data-confirm="Voulez-vous désactiver et supprimer cette configuration de sanction ?"
                                        data-confirm-title="Suppression d’une sanction"
                                        data-confirm-button="Supprimer"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9">Aucune sanction configurée. Créez d’abord une sanction pour permettre au système de proposer ou d’appliquer des mesures.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</x-app-layout>
