<x-app-layout>
{{-- Vue Blade : resources/views/impayes/index.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Liste des impayés</h1>

            <form action="{{ route('impayes.index') }}" method="GET">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        <option value="">Toutes les années</option>

                        {{-- Remplit la liste des annees scolaires. --}}
                        @foreach ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected($selectedAnneeId == $annee->id)>
                                {{ $annee->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Classe</label>
                    <select name="classe_id" class="form-control">
                        <option value="">Toutes les classes</option>

                        {{-- Remplit la liste des classes disponibles. --}}
                        @foreach ($classes as $classe)
                            <option value="{{ $classe->id }}" @selected($selectedClasseId == $classe->id)>
                                {{ $classe->nom }} — {{ $classe->anneeScolaire->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Filtrer
                </button>

                <a href="{{ route('impayes.index') }}" class="btn">
                    Réinitialiser
                </a>
            </form>
        </div>

        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-title">Inscriptions concernées</div>
                <div class="stat-value">{{ $nombreTotalInscriptions }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Élèves en impayé</div>
                <div class="stat-value">{{ $nombreImpayes }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Élèves soldés</div>
                <div class="stat-value">{{ $nombreSoldes }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Taux recouvrement</div>
                <div class="stat-value">
                    {{ number_format($tauxRecouvrement, 2, ',', ' ') }}%
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Frais attendus globaux</div>
                <div class="stat-value">
                    {{ number_format($totalFraisAttendus, 0, ',', ' ') }} FCFA
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Frais collectés globaux</div>
                <div class="stat-value">
                    {{ number_format($totalFraisCollectes, 0, ',', ' ') }} FCFA
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Reste total global</div>
                <div class="stat-value">
                    {{ number_format($totalRestant, 0, ',', ' ') }} FCFA
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Détail des élèves en impayé</h2>
            <p class="text-muted">
                Le tableau ci-dessous affiche uniquement les élèves qui ont encore un reste à payer.
            </p>

            <a href="{{ route('paiements.create') }}" class="btn btn-primary">
                Ajouter paiement
            </a>

            <table class="table">
                <thead>
                    <tr>
                        <th>Matricule</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Classe</th>
                        <th>Année</th>
                        <th>Frais attendus</th>
                        <th>Total payé</th>
                        <th>Reste à payer</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les inscriptions dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($inscriptions as $inscription)
                        <tr>
                            <td>{{ $inscription->eleve->matricule }}</td>
                            <td>{{ $inscription->eleve->nom }}</td>
                            <td>{{ $inscription->eleve->prenom }}</td>
                            <td>{{ $inscription->classe->nom }}</td>
                            <td>{{ $inscription->anneeScolaire->libelle }}</td>

                            <td>
                                {{ number_format($inscription->frais_attendu, 0, ',', ' ') }} FCFA
                            </td>

                            <td>
                                {{ number_format($inscription->total_paye_calcule, 0, ',', ' ') }} FCFA
                            </td>

                            <td>
                                {{ number_format($inscription->reste_calcule, 0, ',', ' ') }} FCFA
                            </td>

                            <td>
                                <a href="{{ route('inscriptions.show', $inscription) }}" class="btn btn-success">
                                    Voir inscription
                                </a>

                                
                            </td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="9">
                                Aucun impayé trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>