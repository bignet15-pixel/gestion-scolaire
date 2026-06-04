<x-app-layout>
{{-- Vue Blade : resources/views/enseignants/show.blade.php --}}
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Détail enseignant</div>

                <h1>{{ $enseignant->name }}</h1>

                <p>
                    Informations personnelles, affectations actives et historique
                    des classes / matières de l’enseignant.
                </p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('enseignants.index') }}" class="btn">
                    Retour
                </a>

                <a href="{{ route('enseignants.edit', $enseignant) }}" class="btn btn-primary">
                    Modifier
                </a>
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-info-card">
                <div class="detail-label">Matricule</div>
                <div class="detail-value">{{ $enseignant->matricule }}</div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Classes actives</div>
                <div class="detail-value">{{ $nombreClasses }}</div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Matières actives</div>
                <div class="detail-value">{{ $nombreMatieres }}</div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Affectations actives</div>
                <div class="detail-value">{{ $nombreAffectationsActives }}</div>
            </div>
        </div>

        <div class="card profile-card">
            <h2>Informations personnelles</h2>

            <div class="profile-grid">
                <div class="profile-row">
                    <span>Nom</span>
                    <strong>{{ $enseignant->nom }}</strong>
                </div>

                <div class="profile-row">
                    <span>Prénom</span>
                    <strong>{{ $enseignant->prenom }}</strong>
                </div>

                <div class="profile-row">
                    <span>Sexe</span>
                    <strong>{{ $enseignant->sexe }}</strong>
                </div>

                <div class="profile-row">
                    <span>Email</span>
                    <strong>{{ $enseignant->email }}</strong>
                </div>

                <div class="profile-row">
                    <span>Téléphone</span>
                    <strong>{{ $enseignant->phone ?? '-' }}</strong>
                </div>

                <div class="profile-row">
                    <span>Adresse</span>
                    <strong>{{ $enseignant->adresse ?? '-' }}</strong>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Classes et matières affectées</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Année scolaire</th>
                        <th>Classe</th>
                        <th>Matière</th>
                        <th>Date début</th>
                        <th>Date fin</th>
                        <th>Statut</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les enseignants affectes, ou le message vide si aucune affectation n existe. --}}
                    @forelse ($enseignant->affectations as $affectation)
                        <tr>
                            <td>{{ $affectation->classe?->anneeScolaire?->libelle ?? '-' }}</td>
                            <td>{{ $affectation->classe?->nom ?? '-' }}</td>
                            <td>{{ $affectation->matiere?->nom ?? '-' }}</td>
                            <td>{{ $affectation->date_debut?->format('d/m/Y') ?? '-' }}</td>
                            <td>{{ $affectation->date_fin?->format('d/m/Y') ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $affectation->statut === 'actif' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $affectation->statut }}
                                </span>
                            </td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="6">
                                Aucune affectation trouvée pour cet enseignant.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>