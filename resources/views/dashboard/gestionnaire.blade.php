<x-app-layout>
{{-- Vue Blade : resources/views/dashboard/gestionnaire.blade.php --}}
    <style>
        .manager-dashboard {
            display: grid;
            gap: 22px;
        }

        .manager-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: center;
            padding: 26px;
            border-radius: 24px;
            color: #ffffff;
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            box-shadow: 0 20px 44px rgba(15, 23, 42, 0.18);
        }

        .manager-hero-kicker,
        .manager-section-kicker {
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            opacity: 0.82;
        }

        .manager-hero h1 {
            margin: 6px 0 8px;
            font-size: clamp(26px, 3vw, 38px);
            line-height: 1.1;
        }

        .manager-hero p {
            margin: 0;
            max-width: 720px;
            color: rgba(255, 255, 255, 0.82);
            line-height: 1.65;
        }

        .manager-hero-meta {
            display: grid;
            gap: 10px;
            min-width: 240px;
        }

        .manager-hero-pill {
            padding: 12px 14px;
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .manager-hero-pill span {
            display: block;
            color: rgba(255, 255, 255, 0.72);
            font-size: 12px;
            font-weight: 800;
        }

        .manager-hero-pill strong {
            display: block;
            margin-top: 4px;
            color: #ffffff;
            font-size: 16px;
        }

        .manager-school-card {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(260px, 0.9fr);
            gap: 18px;
            align-items: stretch;
            padding: 20px;
            border: 1px solid #dbeafe;
            border-radius: 22px;
            background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.07);
        }

        .manager-school-main {
            display: grid;
            gap: 8px;
            align-content: center;
        }

        .manager-school-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .manager-school-name {
            margin: 0;
            color: var(--primary-dark);
            font-size: clamp(24px, 3vw, 34px);
            font-weight: 950;
            line-height: 1.05;
        }

        .manager-school-description {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .manager-school-details {
            display: grid;
            gap: 10px;
        }

        .manager-school-detail {
            padding: 13px 14px;
            border: 1px solid #dbeafe;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.82);
        }

        .manager-school-detail span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .manager-school-detail strong {
            display: block;
            margin-top: 4px;
            color: var(--primary-dark);
            line-height: 1.45;
        }

        .manager-filter-card {
            padding: 18px;
        }

        .manager-metrics-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
        }

        .manager-metric-card {
            position: relative;
            overflow: hidden;
            padding: 18px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07);
        }

        .manager-metric-card::after {
            content: '';
            position: absolute;
            right: -24px;
            top: -24px;
            width: 76px;
            height: 76px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.08);
        }

        .manager-metric-label {
            color: var(--muted);
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .manager-metric-value {
            margin-top: 10px;
            color: var(--primary-dark);
            font-size: 32px;
            font-weight: 950;
            line-height: 1;
        }

        .manager-metric-note {
            margin-top: 8px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .manager-finance-panel {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(280px, 0.7fr);
            gap: 18px;
        }

        .manager-panel,
        .manager-list-panel {
            border: 1px solid var(--border);
            border-radius: 22px;
            background: #ffffff;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.07);
        }

        .manager-panel {
            padding: 22px;
        }

        .manager-panel-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 18px;
        }

        .manager-panel-head h2 {
            margin: 4px 0 4px;
            color: var(--primary-dark);
            font-size: 22px;
        }

        .manager-panel-head p {
            margin: 0;
            color: var(--muted);
            line-height: 1.55;
        }

        .manager-finance-rate {
            min-width: 132px;
            padding: 14px;
            border-radius: 18px;
            color: #ffffff;
            text-align: center;
            background: #16a34a;
        }

        .manager-finance-rate strong {
            display: block;
            font-size: 26px;
            line-height: 1;
        }

        .manager-finance-rate span {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            font-weight: 800;
        }

        .manager-finance-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .manager-finance-item {
            padding: 16px;
            border-radius: 18px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
        }

        .manager-finance-item .label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .manager-finance-item .value {
            margin-top: 8px;
            color: var(--primary-dark);
            font-size: 22px;
            font-weight: 950;
        }

        .manager-finance-progress {
            margin-top: 18px;
        }

        .manager-finance-progress-head {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 900;
        }

        .manager-progress-bar {
            height: 13px;
            overflow: hidden;
            border-radius: 999px;
            background: #e5e7eb;
        }

        .manager-progress-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #16a34a, #22c55e);
        }

        .manager-button-row,
        .manager-quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .manager-two-columns {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .manager-list-panel {
            padding: 20px;
        }

        .manager-list-panel h2 {
            margin: 0 0 14px;
            color: var(--primary-dark);
            font-size: 20px;
        }

        .manager-todo-list,
        .manager-alert-list,
        .manager-feed-list {
            display: grid;
            gap: 10px;
        }

        .manager-todo-item,
        .manager-alert-item,
        .manager-feed-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 13px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #f8fafc;
        }

        .manager-todo-item strong,
        .manager-alert-item strong,
        .manager-feed-item strong {
            display: block;
            color: var(--primary-dark);
            font-size: 14px;
        }

        .manager-todo-item span,
        .manager-alert-item span,
        .manager-feed-item span {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .manager-count-badge {
            min-width: 44px;
            padding: 8px 10px;
            border-radius: 999px;
            color: #ffffff;
            text-align: center;
            font-weight: 950;
            background: var(--primary);
        }

        .manager-count-badge.danger {
            background: #dc2626;
        }

        .manager-count-badge.warning {
            background: #d97706;
        }

        .manager-count-badge.success {
            background: #16a34a;
        }

        .manager-empty {
            padding: 16px;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            color: var(--muted);
            background: #f8fafc;
        }

        .manager-table-card {
            overflow: hidden;
        }

        .manager-section-title {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 14px;
        }

        .manager-section-title h2 {
            margin: 0;
            color: var(--primary-dark);
            font-size: 20px;
        }

        @media (max-width: 1200px) {
            .manager-metrics-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .manager-finance-panel,
            .manager-two-columns {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .manager-hero,
            .manager-school-card {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .manager-hero-meta,
            .manager-finance-grid,
            .manager-metrics-grid {
                grid-template-columns: 1fr;
            }

            .manager-panel-head,
            .manager-section-title {
                display: grid;
            }

            .manager-finance-rate {
                width: 100%;
            }

            .manager-todo-item,
            .manager-alert-item,
            .manager-feed-item {
                grid-template-columns: 1fr;
            }

            .manager-count-badge {
                width: max-content;
            }

            .manager-button-row .btn,
            .manager-quick-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <div class="container manager-dashboard">
        <section class="manager-hero">
            <div>
                <div class="manager-hero-kicker">Centre de contrôle</div>
                <h1>Tableau de bord gestionnaire</h1>
                <p>
                    Suivi rapide des effectifs, des finances, des demandes parentales,
                    de l’assiduité, des sanctions, des annonces et des notifications.
                </p>
            </div>

            <div class="manager-hero-meta">
                <div class="manager-hero-pill">
                    <span>Année scolaire</span>
                    <strong>{{ $annee?->libelle ?? 'Non définie' }}</strong>
                </div>

                <div class="manager-hero-pill">
                    <span>Trimestre actif</span>
                    <strong>{{ $trimestreActif?->nom ?? 'Aucun trimestre actif' }}</strong>
                </div>
            </div>
        </section>

        <section class="manager-school-card">
            <div class="manager-school-main">
                <div class="manager-school-label">Information école</div>
                <h2 class="manager-school-name">{{ $ecoleInfos['nom'] }}</h2>
                <p class="manager-school-description">{{ $ecoleInfos['description'] }}</p>
            </div>

            <div class="manager-school-details">
                <div class="manager-school-detail">
                    <span>Contact</span>
                    <strong>{{ $ecoleInfos['contact'] }}</strong>
                </div>

                <div class="manager-school-detail">
                    <span>Devise</span>
                    <strong>{{ $ecoleInfos['devise'] }}</strong>
                </div>
            </div>
        </section>

        <div class="card manager-filter-card">
            <form action="{{ route('dashboard') }}" method="GET" class="filter-form filter-form-large">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        @foreach ($annees as $anneeOption)
                            <option value="{{ $anneeOption->id }}" @selected((string) $selectedAnneeId === (string) $anneeOption->id)>
                                {{ $anneeOption->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Afficher</button>
                    <a href="{{ route('dashboard') }}" class="btn">Réinitialiser</a>
                </div>
            </form>
        </div>

        <section class="manager-metrics-grid">
            <div class="manager-metric-card">
                <div class="manager-metric-label">Élèves actifs</div>
                <div class="manager-metric-value">{{ $nombreEleves }}</div>
                <div class="manager-metric-note">Élèves inscrits pour l’année sélectionnée.</div>
            </div>

            <div class="manager-metric-card">
                <div class="manager-metric-label">Classes</div>
                <div class="manager-metric-value">{{ $nombreClasses }}</div>
                <div class="manager-metric-note">Classes ouvertes sur l’année.</div>
            </div>

            <div class="manager-metric-card">
                <div class="manager-metric-label">Enseignants</div>
                <div class="manager-metric-value">{{ $nombreEnseignants }}</div>
                <div class="manager-metric-note">Enseignants affectés aux classes.</div>
            </div>

            <div class="manager-metric-card">
                <div class="manager-metric-label">Parents</div>
                <div class="manager-metric-value">{{ $nombreParents }}</div>
                <div class="manager-metric-note">Parents liés aux élèves de l’année.</div>
            </div>

            <div class="manager-metric-card">
                <div class="manager-metric-label">Demandes</div>
                <div class="manager-metric-value">{{ $totalDemandesEnAttente }}</div>
                <div class="manager-metric-note">Demandes parentales en attente.</div>
            </div>
        </section>

        <section class="manager-finance-panel">
            <div class="manager-panel">
                <div class="manager-panel-head">
                    <div>
                        <div class="manager-section-kicker">Finances</div>
                        <h2>Situation financière globale</h2>
                        <p>Suivi des frais scolaires attendus, collectés et restants.</p>
                    </div>

                    <div class="manager-finance-rate">
                        <strong>{{ number_format($tauxRecouvrement, 1, ',', ' ') }}%</strong>
                        <span>Recouvrement</span>
                    </div>
                </div>

                <div class="manager-finance-grid">
                    <div class="manager-finance-item">
                        <div class="label">Frais attendus</div>
                        <div class="value">{{ number_format($totalFraisAttendus, 0, ',', ' ') }} FCFA</div>
                    </div>

                    <div class="manager-finance-item">
                        <div class="label">Frais collectés</div>
                        <div class="value">{{ number_format($totalFraisCollectes, 0, ',', ' ') }} FCFA</div>
                    </div>

                    <div class="manager-finance-item">
                        <div class="label">Reste à payer</div>
                        <div class="value">{{ number_format($totalRestant, 0, ',', ' ') }} FCFA</div>
                    </div>
                </div>

                <div class="manager-finance-progress">
                    <div class="manager-finance-progress-head">
                        <span>Progression des paiements</span>
                        <strong>{{ number_format($tauxRecouvrement, 2, ',', ' ') }}%</strong>
                    </div>

                    <div class="manager-progress-bar">
                        <div class="manager-progress-fill" style="width: {{ min($tauxRecouvrement, 100) }}%;"></div>
                    </div>
                </div>

                <div class="manager-button-row">
                    <a href="{{ route('paiements.index') }}" class="btn btn-primary">Voir les paiements</a>
                    <a href="{{ route('paiements.create') }}" class="btn btn-success">Enregistrer un paiement</a>
                    <a href="{{ route('impayes.index') }}" class="btn btn-danger">Voir les impayés</a>
                </div>
            </div>

            <div class="manager-list-panel">
                <h2>État des soldes</h2>

                <div class="manager-todo-list">
                    <div class="manager-todo-item">
                        <div>
                            <strong>Élèves soldés</strong>
                            <span>Inscriptions dont les frais sont entièrement réglés.</span>
                        </div>
                        <div class="manager-count-badge success">{{ $nombreSoldes }}</div>
                    </div>

                    <div class="manager-todo-item">
                        <div>
                            <strong>Élèves en impayé</strong>
                            <span>Inscriptions avec un reste à payer.</span>
                        </div>
                        <div class="manager-count-badge danger">{{ $nombreImpayes }}</div>
                    </div>

                    <div class="manager-todo-item">
                        <div>
                            <strong>Évaluations créées</strong>
                            <span>Évaluations enregistrées pour l’année.</span>
                        </div>
                        <div class="manager-count-badge">{{ $nombreEvaluations }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="manager-two-columns">
            <div class="manager-list-panel">
                <div class="manager-section-title">
                    <h2>Demandes parentales à traiter</h2>
                    <a href="{{ route('gestionnaire.paiements-declares.index') }}" class="btn">Voir demandes</a>
                </div>

                <div class="manager-todo-list">
                    <div class="manager-todo-item">
                        <div>
                            <strong>Paiements déclarés</strong>
                            <span>Déclarations de paiement en attente de validation.</span>
                        </div>
                        <a class="manager-count-badge {{ $paiementsDeclaresEnAttente > 0 ? 'danger' : 'success' }}" href="{{ route('gestionnaire.paiements-declares.index') }}">
                            {{ $paiementsDeclaresEnAttente }}
                        </a>
                    </div>

                    <div class="manager-todo-item">
                        <div>
                            <strong>Justifications</strong>
                            <span>Justifications d’absence ou de retard à traiter.</span>
                        </div>
                        <a class="manager-count-badge {{ $justificationsEnAttente > 0 ? 'warning' : 'success' }}" href="{{ route('gestionnaire.justifications-parent.index') }}">
                            {{ $justificationsEnAttente }}
                        </a>
                    </div>

                    <div class="manager-todo-item">
                        <div>
                            <strong>Réinscriptions</strong>
                            <span>Demandes de passage ou redoublement envoyées par les parents.</span>
                        </div>
                        <a class="manager-count-badge {{ $reinscriptionsEnAttente > 0 ? 'warning' : 'success' }}" href="{{ route('gestionnaire.demandes-reinscription.index') }}">
                            {{ $reinscriptionsEnAttente }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="manager-list-panel">
                <div class="manager-section-title">
                    <h2>Alertes scolaires</h2>
                    <a href="{{ route('absences-retards.index') }}" class="btn">Voir assiduité</a>
                </div>

                <div class="manager-alert-list">
                    <div class="manager-alert-item">
                        <div>
                            <strong>Absences cette semaine</strong>
                            <span>Absences enregistrées depuis le début de la semaine.</span>
                        </div>
                        <div class="manager-count-badge {{ $absencesSemaine > 0 ? 'danger' : 'success' }}">{{ $absencesSemaine }}</div>
                    </div>

                    <div class="manager-alert-item">
                        <div>
                            <strong>Retards aujourd’hui</strong>
                            <span>Retards enregistrés pour la journée.</span>
                        </div>
                        <div class="manager-count-badge {{ $retardsAujourdhui > 0 ? 'warning' : 'success' }}">{{ $retardsAujourdhui }}</div>
                    </div>

                    <div class="manager-alert-item">
                        <div>
                            <strong>Sanctions récentes</strong>
                            <span>Sanctions appliquées durant les 7 derniers jours.</span>
                        </div>
                        <div class="manager-count-badge {{ $sanctionsRecentes > 0 ? 'danger' : 'success' }}">{{ $sanctionsRecentes }}</div>
                    </div>

                    <div class="manager-alert-item">
                        <div>
                            <strong>Notes faibles</strong>
                            <span>Notes inférieures à 50% du barème sur le trimestre actif.</span>
                        </div>
                        <div class="manager-count-badge {{ $notesFaibles > 0 ? 'warning' : 'success' }}">{{ $notesFaibles }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="manager-panel">
            <div class="manager-panel-head">
                <div>
                    <div class="manager-section-kicker">Actions rapides</div>
                    <h2>Raccourcis de gestion</h2>
                    <p>Accès direct aux tâches fréquentes du gestionnaire.</p>
                </div>
            </div>

            <div class="manager-quick-actions">
                <a href="{{ route('eleves.create') }}" class="btn btn-primary">Ajouter un élève</a>
                <a href="{{ route('inscriptions.create') }}" class="btn btn-primary">Inscrire un élève</a>
                <a href="{{ route('parents.create') }}" class="btn btn-primary">Ajouter un parent</a>
                <a href="{{ route('paiements.create') }}" class="btn btn-success">Paiement</a>
                <a href="{{ route('annonces.create') }}" class="btn btn-primary">Créer une annonce</a>
                <a href="{{ route('absences-retards.create') }}" class="btn btn-danger">Absence / retard</a>
                <a href="{{ route('evaluations.create') }}" class="btn btn-primary">Créer une évaluation</a>
            </div>
        </section>

        <section class="manager-two-columns">
            <div class="manager-list-panel">
                <div class="manager-section-title">
                    <h2>Activité récente</h2>
                    <a href="{{ route('paiements.index') }}" class="btn">Paiements</a>
                </div>

                <div class="manager-feed-list">
                    @forelse ($derniersPaiements as $paiement)
                        <div class="manager-feed-item">
                            <div>
                                <strong>Paiement {{ $paiement->numero_paiement }}</strong>
                                <span>
                                    {{ $paiement->inscription->eleve->nom }} {{ $paiement->inscription->eleve->prenom }} —
                                    {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA
                                </span>
                            </div>
                            <span class="manager-count-badge success">FCFA</span>
                        </div>
                    @empty
                        <div class="manager-empty">Aucun paiement récent.</div>
                    @endforelse

                    @foreach ($dernieresAbsencesRetards as $absenceRetard)
                        <div class="manager-feed-item">
                            <div>
                                <strong>{{ $absenceRetard->libelleType() }}</strong>
                                <span>
                                    {{ $absenceRetard->inscription?->eleve?->nom }} {{ $absenceRetard->inscription?->eleve?->prenom }} —
                                    {{ $absenceRetard->inscription?->classe?->nom ?? '-' }}
                                </span>
                            </div>
                            <span class="manager-count-badge warning">{{ strtoupper(substr($absenceRetard->type, 0, 1)) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="manager-list-panel">
                <div class="manager-section-title">
                    <h2>Communication</h2>
                    <a href="{{ route('annonces.index') }}" class="btn">Annonces</a>
                </div>

                <div class="manager-feed-list">
                    <div class="manager-todo-item">
                        <div>
                            <strong>Annonces actives</strong>
                            <span>Annonces publiées et non expirées.</span>
                        </div>
                        <div class="manager-count-badge">{{ $annoncesActives }}</div>
                    </div>

                    <div class="manager-todo-item">
                        <div>
                            <strong>Notifications non lues</strong>
                            <span>Notifications dans l’espace du gestionnaire.</span>
                        </div>
                        <a href="{{ route('notifications.index') }}" class="manager-count-badge {{ $notificationsNonLues > 0 ? 'warning' : 'success' }}">
                            {{ $notificationsNonLues }}
                        </a>
                    </div>

                    @forelse ($dernieresAnnonces as $annonce)
                        <div class="manager-feed-item">
                            <div>
                                <strong>{{ $annonce->titre }}</strong>
                                <span>{{ $annonce->libelleCible() }} — {{ $annonce->date_publication?->format('d/m/Y H:i') ?? 'Date non définie' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="manager-empty">Aucune annonce publiée récemment.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="manager-two-columns">
            <div class="card manager-table-card">
                <div class="manager-section-title">
                    <h2>Classes actives</h2>
                    <a href="{{ route('classes.index') }}" class="btn">Voir toutes</a>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Classe</th>
                            <th>Niveau</th>
                            <th>Enseignant principal</th>
                            <th>Élèves</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classes as $classe)
                            <tr>
                                <td>{{ $classe->nom }}</td>
                                <td>{{ $classe->niveau }}</td>
                                <td>{{ $classe->enseignantPrincipal?->name ?? '-' }}</td>
                                <td>{{ $classe->inscriptions_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Aucune classe trouvée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card manager-table-card">
                <div class="manager-section-title">
                    <h2>Notes faibles récentes</h2>
                    <a href="{{ route('resultats.index') }}" class="btn">Voir résultats</a>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Classe</th>
                            <th>Matière</th>
                            <th>Trimestre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dernieresNotesFaibles as $note)
                            <tr>
                                <td>{{ $note->inscription?->eleve?->nom }} {{ $note->inscription?->eleve?->prenom }}</td>
                                <td>{{ $note->inscription?->classe?->nom ?? '-' }}</td>
                                <td>{{ $note->evaluation?->matiere?->nom ?? '-' }}</td>
                                <td>{{ $note->evaluation?->trimestre?->nom ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Aucune note faible récente.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
