<x-app-layout>
{{-- Vue Blade : resources/views/eleves/show.blade.php --}}
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Fiche élève</div>

                <h1>{{ $eleve->nom }} {{ $eleve->prenom }}</h1>

                <p>
                    Informations personnelles, parcours scolaire, paiements,
                    notes, moyennes et rangs trimestriels.
                </p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('eleves.index') }}" class="btn">
                    Retour
                </a>

                <a href="{{ route('eleves.edit', $eleve) }}" class="btn btn-primary">
                    Modifier
                </a>
            </div>
        </div>

        <div class="student-profile-card">
            <div class="student-photo-box">
                {{-- Condition : $eleve->photo. --}}
                @if ($eleve->photo)
                    <img src="{{ asset('storage/' . $eleve->photo) }}" alt="Photo élève">
                {{-- Sinon, affichage de l alternative prevue. --}}
                @else
                    <div class="student-photo-placeholder">
                        {{ strtoupper(substr($eleve->nom, 0, 1)) }}{{ strtoupper(substr($eleve->prenom, 0, 1)) }}
                    </div>
                @endif
            </div>

            <div class="student-info">
                <div class="student-name">
                    {{ $eleve->nom }} {{ $eleve->prenom }}
                </div>

                <div class="student-matricule">
                    Matricule : {{ $eleve->matricule }}
                </div>

                <div class="profile-grid">
                    <div class="profile-row">
                        <span>Sexe</span>
                        <strong>{{ $eleve->sexe }}</strong>
                    </div>

                    <div class="profile-row">
                        <span>Date de naissance</span>
                        <strong>{{ $eleve->date_naissance?->format('d/m/Y') ?? '-' }}</strong>
                    </div>

                    <div class="profile-row">
                        <span>Lieu de naissance</span>
                        <strong>{{ $eleve->lieu_naissance ?? '-' }}</strong>
                    </div>

                    <div class="profile-row">
                        <span>Contact parent</span>
                        <strong>{{ $eleve->contact_parent ?? '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Filtrer le parcours</h2>

            <form action="{{ route('eleves.show', $eleve) }}" method="GET" class="filter-form">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        <option value="">Toutes les années</option>

                        {{-- Affiche l historique des inscriptions. --}}
                        @foreach ($inscriptionsOptions->unique('annee_scolaire_id') as $inscriptionOption)
                            <option value="{{ $inscriptionOption->annee_scolaire_id }}" @selected((string) $selectedAnneeId === (string) $inscriptionOption->annee_scolaire_id)>
                                {{ $inscriptionOption->anneeScolaire?->libelle ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Classe</label>
                    <select name="classe_id" class="form-control">
                        <option value="">Toutes les classes</option>

                        {{-- Affiche l historique des inscriptions. --}}
                        @foreach ($inscriptionsOptions as $inscriptionOption)
                            <option value="{{ $inscriptionOption->classe_id }}" @selected((string) $selectedClasseId === (string) $inscriptionOption->classe_id)>
                                {{ $inscriptionOption->classe?->nom ?? '-' }} — {{ $inscriptionOption->anneeScolaire?->libelle ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Afficher
                    </button>

                    <a href="{{ route('eleves.show', $eleve) }}" class="btn">
                        Dernière inscription
                    </a>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Historique des inscriptions</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Année scolaire</th>
                        <th>Classe</th>
                        <th>Date inscription</th>
                        <th>Frais attendus</th>
                        <th>Total payé</th>
                        <th>Reste</th>
                        <th>Statut</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche le parcours scolaire de l eleve, ou le message vide si aucune inscription n existe. --}}
                    @forelse ($eleve->inscriptions as $inscription)
                        <tr>
                            <td>{{ $inscription->anneeScolaire?->libelle }}</td>
                            <td>{{ $inscription->classe?->nom }}</td>
                            <td>{{ $inscription->date_inscription?->format('d/m/Y') }}</td>
                            <td>{{ number_format($inscription->frais_attendu, 0, ',', ' ') }} FCFA</td>
                            <td>{{ number_format($inscription->totalPaye(), 0, ',', ' ') }} FCFA</td>
                            <td>{{ number_format($inscription->resteAPayer(), 0, ',', ' ') }} FCFA</td>
                            <td>
                                <span class="badge {{ $inscription->statut === 'actif' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $inscription->statut }}
                                </span>
                            </td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="7">
                                Aucune inscription trouvée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Affiche les elements de $resultatsParInscription, ou le message vide si aucun resultat n existe. --}}
        @forelse ($resultatsParInscription as $bloc)
            <div class="card">
                <h2>
                    Notes et résultats —
                    {{ $bloc['inscription']->classe?->nom }}
                    /
                    {{ $bloc['inscription']->anneeScolaire?->libelle }}
                </h2>

                {{-- Affiche les resultats par trimestre. --}}
                @foreach ($bloc['trimestres'] as $resultatTrimestre)
                    <div class="trimester-result">
                        <div class="trimester-header">
                            <div>
                                <h3>{{ $resultatTrimestre['trimestre']->nom }}</h3>

                                <p>
                                    Moyenne :
                                    <strong>
                                        {{-- Condition : $resultatTrimestre['moyenne'] !== null. --}}
                                        @if ($resultatTrimestre['moyenne'] !== null)
                                            {{ number_format($resultatTrimestre['moyenne'], 2, ',', ' ') }}/20
                                        {{-- Sinon, affichage de l alternative prevue. --}}
                                        @else
                                            -
                                        @endif
                                    </strong>
                                    |
                                    Rang :
                                    <strong>
                                        {{ $resultatTrimestre['rang'] ?? '-' }}
                                    </strong>
                                    |
                                    Appréciation :
                                    <strong>{{ $resultatTrimestre['appreciation'] }}</strong>
                                </p>
                            </div>
                        </div>

                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Évaluation</th>
                                    <th>Matière</th>
                                    <th>Type</th>
                                    <th>Note</th>
                                    <th>Barème</th>
                                    <th>Appréciation</th>
                                </tr>
                            </thead>

                            <tbody>
                                {{-- Affiche les notes de l evaluation, ou le message vide si aucune note n existe. --}}
                                @forelse ($resultatTrimestre['notes'] as $note)
                                    <tr>
                                        <td>{{ $note->evaluation?->date_evaluation?->format('d/m/Y') ?? '-' }}</td>
                                        <td>{{ $note->evaluation?->nom ?? '-' }}</td>
                                        <td>{{ $note->evaluation?->matiere?->nom ?? '-' }}</td>
                                        <td>{{ $note->evaluation?->type ?? '-' }}</td>
                                        <td>{{ $note->valeur }}</td>
                                        <td>{{ $note->evaluation?->bareme ?? '-' }}</td>
                                        <td>{{ $note->appreciation ?? '-' }}</td>
                                    </tr>
                                {{-- Message affiche quand la liste est vide. --}}
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            Aucune note pour ce trimestre.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        {{-- Message affiche quand la liste est vide. --}}
        @empty
            <div class="card">
                <h2>Notes et résultats</h2>
                <p>Aucun résultat disponible pour cet élève.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
