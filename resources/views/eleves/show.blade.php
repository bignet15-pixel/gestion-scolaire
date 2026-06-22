<x-app-layout>
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

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

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
            <h2>Parents liés</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Parent</th>
                            <th>Contact</th>
                            <th>Lien</th>
                            <th>Responsable principal</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($eleve->parents as $parent)
                            <tr>
                                <td>
                                    {{ $parent->nom }} {{ $parent->prenom }}
                                    <br>
                                    <small>{{ $parent->matricule }}</small>
                                </td>
                                <td>
                                    {{ $parent->phone ?? '-' }}
                                    <br>
                                    <small>{{ $parent->email }}</small>
                                </td>
                                <td>{{ $parent->pivot->lien_parente ?? '-' }}</td>
                                <td>
                                    @if ($parent->pivot->responsable_principal)
                                        <span class="badge badge-success">Oui</span>
                                    @else
                                        <span class="badge badge-muted">Non</span>
                                    @endif
                                </td>
                                <td>
                                    <form
                                        action="{{ route('eleves.parents.destroy', [$eleve, $parent->id]) }}"
                                        method="POST"
                                        data-confirm="Voulez-vous retirer ce parent de cet élève ?"
                                        data-confirm-title="Retrait d’un parent"
                                        data-confirm-button="Retirer"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-danger">
                                            Retirer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    Aucun compte parent n’est lié à cet élève.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <hr>

            <h3>Associer un parent existant</h3>

            <form action="{{ route('eleves.parents.store', $eleve) }}" method="POST" class="form-grid">
                @csrf

                <div class="form-group">
                    <label class="form-label">Compte parent</label>
                    <select name="parent_id" class="form-control" required>
                        <option value="">Choisir un parent</option>

                        @foreach ($parentsDisponibles as $parentDisponible)
                            <option value="{{ $parentDisponible->id }}" @selected(old('parent_id') == $parentDisponible->id)>
                                {{ $parentDisponible->nom }} {{ $parentDisponible->prenom }} — {{ $parentDisponible->phone ?? $parentDisponible->email }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Lien de parenté</label>
                    <input
                        type="text"
                        name="lien_parente"
                        class="form-control"
                        value="{{ old('lien_parente') }}"
                        placeholder="Père, mère, tuteur..."
                    >
                </div>

                <div class="form-group form-group-full">
                    <label class="checkbox-label">
                        <input type="checkbox" name="responsable_principal" value="1" @checked(old('responsable_principal'))>
                        Définir comme responsable principal
                    </label>
                </div>

                <div class="form-actions form-group-full">
                    <a href="{{ route('parents.create') }}" class="btn">
                        Créer un parent
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Associer
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Filtrer le parcours</h2>

            <form action="{{ route('eleves.show', $eleve) }}" method="GET" class="filter-form js-eleve-parcours-filter">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control js-eleve-parcours-annee">
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
                    <select name="classe_id" class="form-control js-eleve-parcours-classe">
                        <option value="">Toutes les classes</option>

                        {{-- Affiche l historique des inscriptions. --}}
                        @foreach ($inscriptionsOptions as $inscriptionOption)
                            <option
                                value="{{ $inscriptionOption->classe_id }}"
                                data-annee="{{ $inscriptionOption->annee_scolaire_id }}"
                                @selected((string) $selectedClasseId === (string) $inscriptionOption->classe_id)
                            >
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

                                @if (! $resultatTrimestre['publie'])
                                    <span class="badge {{ $resultatTrimestre['statut_badge'] }}">
                                        {{ $resultatTrimestre['statut_libelle'] }}
                                    </span>
                                @endif
                            </div>

                            @if ($resultatTrimestre['publie'])
                                <a
                                    href="{{ route('bulletins.trimestriel', [$bloc['inscription'], $resultatTrimestre['trimestre']]) }}"
                                    class="btn btn-primary"
                                >
                                    Bulletin PDF
                                </a>
                            @endif
                        </div>

                        @if ($resultatTrimestre['publie'])
                            <div class="trimester-summary-grid">
                                <div class="trimester-summary-item">
                                    <span>Moyenne finale</span>
                                    <strong>
                                        @if ($resultatTrimestre['moyenne'] !== null)
                                            {{ number_format($resultatTrimestre['moyenne'], 2, ',', ' ') }}/20
                                        @else
                                            -
                                        @endif
                                    </strong>
                                </div>

                                <div class="trimester-summary-item">
                                    <span>Rang</span>
                                    <strong>{{ $resultatTrimestre['rang'] ?? '-' }}</strong>
                                </div>

                                <div class="trimester-summary-item">
                                    <span>Total pondéré</span>
                                    <strong>
                                        {{ $resultatTrimestre['total_pondere'] !== null ? number_format($resultatTrimestre['total_pondere'], 2, ',', ' ') : '-' }}
                                    </strong>
                                </div>

                                <div class="trimester-summary-item">
                                    <span>Coefficients</span>
                                    <strong>{{ number_format($resultatTrimestre['total_coefficients'], 2, ',', ' ') }}</strong>
                                </div>

                                @php
                                    $retenuesEnCours = $resultatTrimestre['total_points_en_moins_en_cours'] ?? 0;
                                    $retenuesDefinitives = $resultatTrimestre['total_points_en_moins_definitifs'] ?? ($resultatTrimestre['total_points_en_moins'] ?? 0);
                                @endphp

                                @if ($retenuesEnCours > 0 || $retenuesDefinitives > 0)
                                    <div class="trimester-summary-item">
                                        <span>Retenues discipline / assiduité</span>
                                        <strong>
                                            @if ($retenuesEnCours > 0)
                                                -{{ number_format($retenuesEnCours, 2, ',', ' ') }} en cours
                                            @endif
                                            @if ($retenuesEnCours > 0 && $retenuesDefinitives > 0)
                                                <br>
                                            @endif
                                            @if ($retenuesDefinitives > 0)
                                                -{{ number_format($retenuesDefinitives, 2, ',', ' ') }} définitif
                                            @endif
                                        </strong>
                                    </div>

                                    <div class="trimester-summary-item">
                                        <span>Total pondéré final</span>
                                        <strong>{{ number_format($resultatTrimestre['total_pondere_final'], 2, ',', ' ') }}</strong>
                                    </div>
                                @endif

                                <div class="trimester-summary-item trimester-summary-wide">
                                    <span>Appréciation</span>
                                    <strong>{{ $resultatTrimestre['appreciation'] }}</strong>
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
                                                Aucune note publiée pour ce trimestre.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @else
                            <div class="result-unpublished-box">
                                @if ($resultatTrimestre['statut_pedagogique'] === 'pas_encore_programme')
                                    Ce trimestre n’est pas encore programmé.
                                @elseif ($resultatTrimestre['statut_pedagogique'] === 'en_cours')
                                    Le trimestre est en cours. Les notes, la moyenne et le rang seront affichés après la fin du trimestre.
                                @elseif ($resultatTrimestre['notes_manquantes'] > 0)
                                    Ce trimestre est terminé, mais {{ $resultatTrimestre['notes_manquantes'] }} note(s) attendue(s) ne sont pas encore saisie(s).
                                @elseif ($resultatTrimestre['evaluations_attendues'] === 0)
                                    Aucune évaluation n’est programmée pour ce trimestre.
                                @else
                                    Les résultats de ce trimestre ne sont pas encore publiés.
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach

                <div class="annual-result-card">
                    <div class="annual-result-head">
                        <div>
                            <h3>Résultat annuel</h3>
                            <p>
                                {{ $bloc['inscription']->classe?->nom }}
                                /
                                {{ $bloc['inscription']->anneeScolaire?->libelle }}
                            </p>
                        </div>

                        @if ($bloc['annuel']['publie'])
                            <div class="annual-result-actions">
                                <span class="badge {{ $bloc['annuel']['decision'] === 'Passe' ? 'badge-success' : 'badge-danger' }}">
                                    {{ $bloc['annuel']['decision'] }}
                                </span>

                                <a href="{{ route('bulletins.annuel', $bloc['inscription']) }}" class="btn btn-primary">
                                    Bulletin annuel PDF
                                </a>
                            </div>
                        @else
                            <span class="badge badge-muted">
                                Non disponible
                            </span>
                        @endif
                    </div>

                    @if ($bloc['annuel']['publie'])
                        <div class="annual-result-grid">
                            <div>
                                <span>Moyenne annuelle</span>
                                <strong>{{ number_format($bloc['annuel']['moyenne'], 2, ',', ' ') }}/20</strong>
                            </div>

                            <div>
                                <span>Rang annuel</span>
                                <strong>{{ $bloc['annuel']['rang'] ?? '-' }}</strong>
                            </div>

                            <div>
                                <span>Appréciation</span>
                                <strong>{{ $bloc['annuel']['appreciation'] }}</strong>
                            </div>
                        </div>
                    @else
                        <div class="result-unpublished-box">
                            {{ $bloc['annuel']['message'] }}
                        </div>
                    @endif
                </div>
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
