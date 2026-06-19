<x-app-layout>
{{-- Vue Blade : resources/views/classes/show.blade.php --}}
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Détail de classe</div>
                <h1>{{ $classe->nom }}</h1>

                <p>
                    Informations générales, enseignants intervenants et élèves inscrits.
                </p>
            </div>

            <div class="detail-actions">
                <a href="{{ $retourUrl ?? route('classes.index') }}" class="btn">
                    Retour
                </a>

                {{-- Condition : isset($pdfUrl). --}}
                @if (isset($pdfUrl))
                    <a href="{{ $pdfUrl }}" class="btn btn-success">
                        Imprimer liste élèves
                    </a>
                @endif

                {{-- Condition : ! $classe->anneeScolaire?->estFermee(). --}}
                @if (auth()->user()->estGestionnaire() && ! $classe->anneeScolaire?->estFermee())
                    <a href="{{ route('classes.edit', $classe) }}" class="btn btn-primary">
                        Modifier
                    </a>
                @endif
            </div>
        </div>

        {{-- Résumé principal de la classe, avec le chef de classe affiché sans photo. --}}
        <div class="detail-grid class-detail-grid">
            <div class="detail-info-card">
                <div class="detail-label">Année scolaire</div>
                <div class="detail-value">{{ $classe->anneeScolaire->libelle }}</div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Niveau</div>
                <div class="detail-value">{{ $classe->niveau }}</div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Effectif</div>
                <div class="detail-value">{{ $inscriptions->count() }}</div>
            </div>

            {{-- Condition : auth()->user()->estGestionnaire(). --}}
            @if (auth()->user()->estGestionnaire())
                <div class="detail-info-card">
                    <div class="detail-label">Frais de scolarité</div>
                    <div class="detail-value">
                        {{ number_format($classe->frais_scolarite, 0, ',', ' ') }} FCFA
                    </div>
                </div>
            @endif

            <div class="detail-info-card">
                <div class="detail-label">Enseignant principal</div>
                <div class="detail-value">
                    {{ $classe->enseignantPrincipal?->name ?? 'Non affecté' }}
                </div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Chef de classe</div>
                <div class="detail-value">
                    {{-- Condition : $classe->chefClasse. --}}
                    @if ($classe->chefClasse)
                        {{ $classe->chefClasse->nom }} {{ $classe->chefClasse->prenom }}  {{ $classe->chefClasse->matricule }}
                    {{-- Sinon, affichage de l alternative prevue. --}}
                    @else
                        Non désigné
                    @endif
                </div>
            </div>
        </div>

        @if (isset($planningUrl, $planningPdfUrl))
            <div class="card class-planning-card">
                <div>
                    <h2>Emploi du temps</h2>

                    <p>
                        Planning hebdomadaire de la classe {{ $classe->nom }}
                        pour l’année scolaire {{ $classe->anneeScolaire->libelle }}.
                    </p>
                </div>

                <div class="detail-actions">
                    <a href="{{ $planningUrl }}" class="btn btn-primary">
                        Voir le planning
                    </a>

                    <a href="{{ $planningPdfUrl }}" class="btn btn-success">
                        Imprimer le planning
                    </a>
                </div>
            </div>
        @endif

        <div class="card">
            <h2>{{ $affectationsTitre ?? 'Enseignants intervenants' }}</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Enseignant</th>
                        <th>Matière</th>
                        <th>Coefficient</th>
                        <th>Statut</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les enseignants affectes, ou le message vide si aucune affectation n existe. --}}
                    @forelse ($classe->affectations as $affectation)
                        <tr>
                            <td>{{ $affectation->enseignant?->name ?? '-' }}</td>
                            <td>{{ $affectation->matiere?->nom ?? '-' }}</td>
                            <td>{{ number_format($affectation->coefficient, 2, ',', ' ') }}</td>
                            <td>
                                <span class="badge {{ $affectation->statut === 'actif' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $affectation->statut }}
                                </span>
                            </td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="4">
                                Aucun enseignant intervenant pour cette classe.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Élèves inscrits</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Matricule</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Statut inscription</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les inscriptions dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($inscriptions as $inscription)
                        <tr>
                            <td>
                                {{-- Condition : $inscription->eleve->photo. --}}
                                @if ($inscription->eleve->photo)
                                    <img src="{{ asset('storage/' . $inscription->eleve->photo) }}" alt="Photo" width="45">
                                {{-- Sinon, affichage de l alternative prevue. --}}
                                @else
                                    -
                                @endif

                                {{-- Condition : (int) $classe->chef_classe_id === (int) $inscription->eleve_id. --}}
                                @if ((int) $classe->chef_classe_id === (int) $inscription->eleve_id)
                                    <div>
                                        <span class="badge badge-success">
                                            Chef
                                        </span>
                                    </div>
                                @endif
                            </td>

                            <td>{{ $inscription->eleve->matricule }}</td>
                            <td>{{ $inscription->eleve->nom }}</td>
                            <td>{{ $inscription->eleve->prenom }}</td>
                            <td>{{ $inscription->statut }}</td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="5">
                                Aucun élève inscrit dans cette classe.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
