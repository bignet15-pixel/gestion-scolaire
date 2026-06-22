<x-app-layout>
    <div class="container">
        <div class="card">
            <div class="detail-header-card">
                <div>
                    <div class="detail-kicker">Administration</div>
                    <h1>Parents</h1>
                    <p>Gestion des comptes parents et responsables d’élèves.</p>
                </div>

                <div class="detail-actions">
                    <a href="{{ route('parents.create') }}" class="btn btn-primary">
                        Ajouter un parent
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom complet</th>
                            <th>Sexe</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Enfants liés</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($parents as $parent)
                            <tr>
                                <td>{{ $parent->matricule }}</td>
                                <td>{{ $parent->nom }} {{ $parent->prenom }}</td>
                                <td>{{ $parent->sexe ?? '-' }}</td>
                                <td>{{ $parent->email }}</td>
                                <td>{{ $parent->phone ?? '-' }}</td>
                                <td>{{ $parent->enfants_count }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('parents.edit', $parent) }}" class="btn btn-primary">
                                            Modifier
                                        </a>

                                        <form
                                            action="{{ route('parents.destroy', $parent) }}"
                                            method="POST"
                                            data-confirm="Voulez-vous vraiment supprimer ce compte parent ? Les liaisons avec les élèves seront retirées."
                                            data-confirm-title="Suppression d’un parent"
                                            data-confirm-button="Supprimer"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-danger">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    Aucun compte parent trouvé.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
