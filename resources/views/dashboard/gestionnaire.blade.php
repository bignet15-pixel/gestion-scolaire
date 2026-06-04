<x-app-layout>
{{-- Vue Blade : resources/views/dashboard/gestionnaire.blade.php --}}
    <div class="container">
        <h1>Tableau de bord gestionnaire</h1>
        <div class="school-info-card">
        <div class="school-info-logo">
            BZ
        </div>

        <div class="school-info-content">
            <div class="school-info-kicker">Information école</div>

            <h2>{{ config('ecole.nom') }}</h2>

            <p class="school-info-devise">
                {{ config('ecole.devise') }}
            </p>

            <div class="school-info-meta">
                <span>
                    <strong>Contact :</strong>
                    {{ config('ecole.contact') }}
                </span>

                <span>
                    <strong>Directeur :</strong>
                    {{ config('ecole.directeur') }}
                </span>
            </div>
        </div>
    </div>

        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-title">Élèves</div>
                <div class="stat-value">{{ $nombreEleves }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Classes</div>
                <div class="stat-value">{{ $nombreClasses }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Enseignants</div>
                <div class="stat-value">{{ $nombreEnseignants }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Inscriptions</div>
                <div class="stat-value">{{ $nombreInscriptions }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Évaluations</div>
                <div class="stat-value">{{ $nombreEvaluations }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Élèves en impayé</div>
                <div class="stat-value">{{ $nombreImpayes }}</div>
            </div>
        </div>

        <div class="card finance-panel">
            <div class="finance-header">
                <div>
                    <h2>Situation financière globale</h2>
                    <p>
                        Vue d’ensemble des frais scolaires, des paiements collectés
                        et des montants restant à payer.
                    </p>
                </div>

                <div class="finance-rate">
                    <span>{{ number_format($tauxRecouvrement, 2, ',', ' ') }}%</span>
                    <small>Taux de recouvrement</small>
                </div>
            </div>

            <div class="finance-grid">
                <div class="finance-item">
                    <div class="finance-label">Frais attendus</div>
                    <div class="finance-value">
                        {{ number_format($totalFraisAttendus, 0, ',', ' ') }} FCFA
                    </div>
                </div>

                <div class="finance-item success">
                    <div class="finance-label">Frais collectés</div>
                    <div class="finance-value">
                        {{ number_format($totalFraisCollectes, 0, ',', ' ') }} FCFA
                    </div>
                </div>

                <div class="finance-item danger">
                    <div class="finance-label">Reste total</div>
                    <div class="finance-value">
                        {{ number_format($totalRestant, 0, ',', ' ') }} FCFA
                    </div>
                </div>

                <div class="finance-item warning">
                    <div class="finance-label">Élèves soldés</div>
                    <div class="finance-value">
                        {{ $nombreSoldes }}
                    </div>
                </div>
            </div>

            <div class="finance-progress">
                <div class="finance-progress-head">
                    <span>Progression des paiements</span>
                    <strong>{{ number_format($tauxRecouvrement, 2, ',', ' ') }}%</strong>
                </div>

                <div class="finance-progress-bar">
                    <div
                        class="finance-progress-fill"
                        style="width: {{ min($tauxRecouvrement, 100) }}%;"
                    ></div>
                </div>
            </div>

            <div class="finance-actions">
                <a href="{{ route('impayes.index') }}" class="btn btn-danger">
                    Voir les impayés
                </a>

                <a href="{{ route('paiements.index') }}" class="btn btn-primary">
                    Voir les paiements
                </a>

                <a href="{{ route('inscriptions.index') }}" class="btn btn-success">
                    Voir les inscriptions
                </a>
            </div>
        </div>

        <div class="card">
            <h2>Accès rapides</h2>

            <p>
                <a href="{{ route('eleves.index') }}" class="btn btn-primary">Élèves</a>
                <a href="{{ route('inscriptions.index') }}" class="btn btn-primary">Inscriptions</a>
                <a href="{{ route('classes.index') }}" class="btn btn-primary">Classes</a>
                <a href="{{ route('enseignants.index') }}" class="btn btn-primary">Enseignants</a>
                <a href="{{ route('evaluations.index') }}" class="btn btn-primary">Évaluations</a>
                <a href="{{ route('resultats.index') }}" class="btn btn-success">Moyennes / Classements</a>
            </p>
        </div>

        <div class="card">
            <h2>Classes</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Année</th>
                        <th>Classe</th>
                        <th>Niveau</th>
                        <th>Enseignant principal</th>
                        <th>Élèves</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les classes dans le tableau, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($classes as $classe)
                        <tr>
                            <td>{{ $classe->anneeScolaire->libelle }}</td>
                            <td>{{ $classe->nom }}</td>
                            <td>{{ $classe->niveau }}</td>
                            <td>{{ $classe->enseignantPrincipal?->name ?? '-' }}</td>
                            <td>{{ $classe->inscriptions_count }}</td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="5">Aucune classe trouvée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Derniers paiements</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Élève</th>
                        <th>Classe</th>
                        <th>Montant</th>
                        <th>Gestionnaire</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les derniers paiements du tableau de bord, ou le message vide si aucun resultat n existe. --}}
                    @forelse ($derniersPaiements as $paiement)
                        <tr>
                            <td>{{ $paiement->numero_paiement }}</td>
                            <td>
                                {{ $paiement->inscription->eleve->nom }}
                                {{ $paiement->inscription->eleve->prenom }}
                            </td>
                            <td>{{ $paiement->inscription->classe->nom }}</td>
                            <td>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                            <td>{{ $paiement->gestionnaire?->name ?? '-' }}</td>
                        </tr>
                    {{-- Message affiche quand la liste est vide. --}}
                    @empty
                        <tr>
                            <td colspan="5">Aucun paiement enregistré.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>