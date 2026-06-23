<x-app-layout>
{{-- Vue Blade : resources/views/dashboard/enseignant.blade.php --}}
    <style>
        .teacher-dashboard {
            display: grid;
            gap: 22px;
        }

        .teacher-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: center;
            padding: 26px;
            border-radius: 24px;
            color: #ffffff;
            background: linear-gradient(135deg, #1e3a8a 0%, #111827 100%);
            box-shadow: 0 20px 44px rgba(15, 23, 42, 0.18);
        }

        .teacher-hero-kicker,
        .teacher-section-kicker {
            font-size: 12px;
            font-weight: 950;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            opacity: 0.82;
        }

        .teacher-hero h1 {
            margin: 6px 0 8px;
            font-size: clamp(26px, 3vw, 38px);
            line-height: 1.1;
        }

        .teacher-hero p {
            margin: 0;
            max-width: 760px;
            color: rgba(255, 255, 255, 0.82);
            line-height: 1.65;
        }

        .teacher-hero-meta {
            display: grid;
            gap: 10px;
            min-width: 240px;
        }

        .teacher-hero-pill {
            padding: 12px 14px;
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .teacher-hero-pill span {
            display: block;
            color: rgba(255, 255, 255, 0.72);
            font-size: 12px;
            font-weight: 850;
        }

        .teacher-hero-pill strong {
            display: block;
            margin-top: 4px;
            color: #ffffff;
            font-size: 16px;
        }

        .teacher-filter-card {
            padding: 18px;
        }

        .teacher-metrics-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
        }

        .teacher-metric-card {
            position: relative;
            overflow: hidden;
            padding: 18px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07);
        }

        .teacher-metric-card::after {
            content: '';
            position: absolute;
            right: -24px;
            top: -24px;
            width: 76px;
            height: 76px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.08);
        }

        .teacher-metric-label {
            color: var(--muted);
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .teacher-metric-value {
            margin-top: 10px;
            color: var(--primary-dark);
            font-size: 32px;
            font-weight: 950;
            line-height: 1;
        }

        .teacher-metric-note {
            margin-top: 8px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .teacher-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
            gap: 18px;
            align-items: start;
        }

        .teacher-two-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            align-items: start;
        }

        .teacher-panel,
        .teacher-list-panel {
            border: 1px solid var(--border);
            border-radius: 22px;
            background: #ffffff;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.07);
        }

        .teacher-panel {
            padding: 22px;
        }

        .teacher-panel-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 18px;
        }

        .teacher-panel-head h2 {
            margin: 4px 0 4px;
            color: var(--primary-dark);
            font-size: 22px;
        }

        .teacher-panel-head p {
            margin: 0;
            color: var(--muted);
            line-height: 1.55;
        }

        .teacher-pill-counter {
            min-width: 92px;
            padding: 12px 14px;
            border-radius: 18px;
            color: #ffffff;
            text-align: center;
            background: #2563eb;
        }

        .teacher-pill-counter strong {
            display: block;
            font-size: 24px;
            line-height: 1;
        }

        .teacher-pill-counter span {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            font-weight: 800;
        }

        .teacher-actions-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .teacher-action-card {
            display: block;
            padding: 16px;
            border: 1px solid #dbeafe;
            border-radius: 18px;
            color: var(--primary-dark);
            text-decoration: none;
            background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .teacher-action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.14);
        }

        .teacher-action-card strong {
            display: block;
            font-size: 15px;
        }

        .teacher-action-card span {
            display: block;
            margin-top: 6px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .teacher-list {
            display: grid;
            gap: 10px;
        }

        .teacher-list-item {
            display: grid;
            gap: 5px;
            padding: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #f8fafc;
        }

        .teacher-list-item strong {
            color: var(--primary-dark);
            font-size: 15px;
            line-height: 1.35;
        }

        .teacher-list-item span {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .teacher-item-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .teacher-empty {
            padding: 16px;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            color: var(--muted);
            background: #f8fafc;
            line-height: 1.55;
        }

        .teacher-badge-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .teacher-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 9px;
            border-radius: 999px;
            color: #1e3a8a;
            background: #dbeafe;
            font-size: 12px;
            font-weight: 850;
        }

        .teacher-badge-warning {
            color: #92400e;
            background: #fef3c7;
        }

        .teacher-badge-danger {
            color: #991b1b;
            background: #fee2e2;
        }

        .teacher-badge-success {
            color: #166534;
            background: #dcfce7;
        }

        .teacher-table-wrap {
            overflow-x: auto;
        }

        @media (max-width: 1180px) {
            .teacher-metrics-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .teacher-main-grid,
            .teacher-two-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 780px) {
            .teacher-hero {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .teacher-hero-meta {
                min-width: 0;
            }

            .teacher-metrics-grid,
            .teacher-actions-grid {
                grid-template-columns: 1fr;
            }

            .teacher-panel-head {
                flex-direction: column;
            }

            .teacher-pill-counter {
                width: 100%;
            }
        }
    </style>

    <div class="container teacher-dashboard">
        <section class="teacher-hero">
            <div>
                <div class="teacher-hero-kicker">Espace enseignant</div>
                <h1>Bonjour, {{ Auth::user()->prenom ?? Auth::user()->name ?? 'enseignant' }}</h1>
                <p>
                    Retrouvez vos classes, vos matières, les évaluations à suivre,
                    les notes à compléter, votre emploi du temps et les informations importantes de l’école.
                </p>
            </div>

            <div class="teacher-hero-meta">
                <div class="teacher-hero-pill">
                    <span>Année scolaire</span>
                    <strong>{{ $annee?->libelle ?? 'Non définie' }}</strong>
                </div>
                <div class="teacher-hero-pill">
                    <span>Trimestre actif</span>
                    <strong>{{ $trimestreActif?->nom ?? 'Aucun trimestre actif' }}</strong>
                </div>
            </div>
        </section>

        <div class="card teacher-filter-card">
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

        <section class="teacher-metrics-grid">
            <div class="teacher-metric-card">
                <div class="teacher-metric-label">Classes affectées</div>
                <div class="teacher-metric-value">{{ $nombreClasses }}</div>
                <div class="teacher-metric-note">Classes où vous intervenez.</div>
            </div>
            <div class="teacher-metric-card">
                <div class="teacher-metric-label">Matières</div>
                <div class="teacher-metric-value">{{ $nombreMatieres }}</div>
                <div class="teacher-metric-note">Matières associées à vos affectations.</div>
            </div>
            <div class="teacher-metric-card">
                <div class="teacher-metric-label">Élèves concernés</div>
                <div class="teacher-metric-value">{{ $nombreEleves }}</div>
                <div class="teacher-metric-note">Élèves inscrits dans vos classes.</div>
            </div>
            <div class="teacher-metric-card">
                <div class="teacher-metric-label">Évaluations</div>
                <div class="teacher-metric-value">{{ $nombreEvaluations }}</div>
                <div class="teacher-metric-note">Évaluations liées à vos matières.</div>
            </div>
            <div class="teacher-metric-card">
                <div class="teacher-metric-label">Notes saisies</div>
                <div class="teacher-metric-value">{{ $nombreNotesSaisies }}</div>
                <div class="teacher-metric-note">Notes déjà enregistrées.</div>
            </div>
        </section>

        <section class="teacher-main-grid">
            <div class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <div class="teacher-section-kicker">Emploi du temps</div>
                        <h2>Cours d’aujourd’hui</h2>
                        <p>Les créneaux programmés pour la journée courante.</p>
                    </div>
                    <a href="{{ route('emplois-du-temps.semaine-enseignant') }}" class="btn btn-primary">Voir la semaine</a>
                </div>

                <div class="teacher-list">
                    @forelse ($coursAujourdhui as $emploi)
                        <div class="teacher-list-item">
                            <strong>
                                {{ $emploi->heure_debut?->format('H:i') }} - {{ $emploi->heure_fin?->format('H:i') }}
                                · {{ $emploi->affectation?->classe?->nom }}
                            </strong>
                            <span>{{ $emploi->affectation?->matiere?->nom }}{{ $emploi->salle ? ' · Salle '.$emploi->salle : '' }}</span>
                        </div>
                    @empty
                        <div class="teacher-empty">Aucun cours prévu aujourd’hui.</div>
                    @endforelse
                </div>
            </div>

            <div class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <div class="teacher-section-kicker">Actions rapides</div>
                        <h2>Raccourcis de travail</h2>
                        <p>Accès direct aux tâches pédagogiques les plus fréquentes.</p>
                    </div>
                </div>

                <div class="teacher-actions-grid">
                    <a href="{{ route('evaluations.create') }}" class="teacher-action-card">
                        <strong>Créer une évaluation</strong>
                        <span>Programmer devoir, interrogation ou composition.</span>
                    </a>
                    <a href="{{ route('evaluations.index') }}" class="teacher-action-card">
                        <strong>Saisir les notes</strong>
                        <span>Choisir une évaluation puis renseigner les notes.</span>
                    </a>
                    <a href="{{ route('absences-retards.create') }}" class="teacher-action-card">
                        <strong>Ajouter absence / retard</strong>
                        <span>Signaler rapidement un cas d’assiduité.</span>
                    </a>
                    <a href="{{ route('enseignant.classes.index') }}" class="teacher-action-card">
                        <strong>Mes classes</strong>
                        <span>Voir les classes et élèves concernés.</span>
                    </a>
                    <a href="{{ route('annonces-ecole.index') }}" class="teacher-action-card">
                        <strong>Annonces</strong>
                        <span>Consulter les informations de l’école.</span>
                    </a>
                    <a href="{{ route('notifications.index') }}" class="teacher-action-card">
                        <strong>Notifications</strong>
                        <span>{{ $notificationsNonLues }} non lue(s).</span>
                    </a>
                </div>
            </div>
        </section>

        <section class="teacher-two-grid">
            <div class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <div class="teacher-section-kicker">Notes</div>
                        <h2>Évaluations à compléter</h2>
                        <p>Évaluations dont toutes les notes des élèves ne sont pas encore saisies.</p>
                    </div>
                    <div class="teacher-pill-counter">
                        <strong>{{ $evaluationsACompleter->count() }}</strong>
                        <span>à suivre</span>
                    </div>
                </div>

                <div class="teacher-list">
                    @forelse ($evaluationsACompleter as $evaluation)
                        <div class="teacher-list-item">
                            <strong>{{ $evaluation->nom }} · {{ $evaluation->classe?->nom }}</strong>
                            <span>{{ $evaluation->matiere?->nom }} · {{ $evaluation->notes_count }} note(s) saisie(s){{ $evaluation->total_eleves_attendus > 0 ? ' / '.$evaluation->total_eleves_attendus : '' }}</span>
                            <div class="teacher-item-actions">
                                <a href="{{ route('notes.saisie', $evaluation) }}" class="btn btn-primary">Saisir</a>
                            </div>
                        </div>
                    @empty
                        <div class="teacher-empty">Aucune évaluation incomplète détectée.</div>
                    @endforelse
                </div>
            </div>

            <div class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <div class="teacher-section-kicker">Alertes pédagogiques</div>
                        <h2>Notes faibles récentes</h2>
                        <p>Notes inférieures à 50% du barème pour vos évaluations.</p>
                    </div>
                    <div class="teacher-pill-counter" style="background:#dc2626">
                        <strong>{{ $nombreNotesFaibles }}</strong>
                        <span>récente(s)</span>
                    </div>
                </div>

                <div class="teacher-list">
                    @forelse ($notesFaiblesRecentes as $note)
                        <div class="teacher-list-item">
                            <strong>{{ $note->inscription?->eleve?->nom }} {{ $note->inscription?->eleve?->prenom }}</strong>
                            <span>
                                {{ $note->evaluation?->matiere?->nom }} · {{ $note->inscription?->classe?->nom }}
                                · {{ number_format((float) $note->valeur, 2, ',', ' ') }}/{{ number_format((float) $note->evaluation?->bareme, 2, ',', ' ') }}
                            </span>
                        </div>
                    @empty
                        <div class="teacher-empty">Aucune note faible récente.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="teacher-two-grid">
            <div class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <div class="teacher-section-kicker">Assiduité</div>
                        <h2>Absences et retards</h2>
                        <p>Résumé des cas enregistrés par vous récemment.</p>
                    </div>
                </div>

                <div class="teacher-badge-row">
                    <span class="teacher-badge teacher-badge-danger">{{ $absencesSemaine }} absence(s) cette semaine</span>
                    <span class="teacher-badge teacher-badge-warning">{{ $retardsAujourdhui }} retard(s) aujourd’hui</span>
                </div>

                <div class="teacher-list" style="margin-top:14px">
                    @forelse ($dernieresAbsencesRetards as $absenceRetard)
                        <div class="teacher-list-item">
                            <strong>{{ $absenceRetard->libelleType() }} · {{ $absenceRetard->inscription?->eleve?->nom }} {{ $absenceRetard->inscription?->eleve?->prenom }}</strong>
                            <span>{{ $absenceRetard->inscription?->classe?->nom }} · {{ $absenceRetard->date_debut?->format('d/m/Y') }} · {{ $absenceRetard->libelleStatut() }}</span>
                        </div>
                    @empty
                        <div class="teacher-empty">Aucun cas récent enregistré par vous.</div>
                    @endforelse
                </div>

                <div class="teacher-item-actions" style="margin-top:14px">
                    <a href="{{ route('absences-retards.index') }}" class="btn">Voir tout</a>
                    <a href="{{ route('absences-retards.create') }}" class="btn btn-primary">Ajouter</a>
                </div>
            </div>

            <div class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <div class="teacher-section-kicker">Communication</div>
                        <h2>Annonces et notifications</h2>
                        <p>Informations de l’école et notifications qui vous concernent.</p>
                    </div>
                </div>

                <div class="teacher-badge-row">
                    <span class="teacher-badge teacher-badge-success">{{ $annoncesActives }} annonce(s) active(s)</span>
                    <span class="teacher-badge teacher-badge-warning">{{ $notificationsNonLues }} notification(s) non lue(s)</span>
                </div>

                <div class="teacher-list" style="margin-top:14px">
                    @forelse ($dernieresAnnonces as $annonce)
                        <div class="teacher-list-item">
                            <strong>{{ $annonce->titre }}</strong>
                            <span>{{ $annonce->libelleCible() }} · {{ $annonce->date_publication?->format('d/m/Y H:i') ?? $annonce->created_at?->format('d/m/Y H:i') }}</span>
                        </div>
                    @empty
                        <div class="teacher-empty">Aucune annonce active.</div>
                    @endforelse
                </div>

                <div class="teacher-item-actions" style="margin-top:14px">
                    <a href="{{ route('annonces-ecole.index') }}" class="btn">Voir annonces</a>
                    <a href="{{ route('notifications.index') }}" class="btn btn-primary">Notifications</a>
                </div>
            </div>
        </section>

        <section class="teacher-two-grid">
            <div class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <div class="teacher-section-kicker">Affectations</div>
                        <h2>Mes classes et matières</h2>
                        <p>Vue synthétique des classes et matières qui vous sont attribuées.</p>
                    </div>
                    <a href="{{ route('enseignant.classes.index') }}" class="btn">Mes classes</a>
                </div>

                <div class="teacher-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Année</th>
                                <th>Classe</th>
                                <th>Matière</th>
                                <th>Début</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($affectations as $affectation)
                                <tr>
                                    <td>{{ $affectation->classe?->anneeScolaire?->libelle }}</td>
                                    <td>{{ $affectation->classe?->nom }}</td>
                                    <td>{{ $affectation->matiere?->nom }}</td>
                                    <td>{{ $affectation->date_debut?->format('d/m/Y') }}</td>
                                    <td>{{ ucfirst($affectation->statut) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">Aucune affectation trouvée.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <div class="teacher-section-kicker">Évaluations</div>
                        <h2>Mes dernières évaluations</h2>
                        <p>Dernières évaluations créées ou liées à vos affectations.</p>
                    </div>
                    <a href="{{ route('evaluations.index') }}" class="btn">Voir tout</a>
                </div>

                <div class="teacher-list">
                    @forelse ($evaluationsRecentes as $evaluation)
                        <div class="teacher-list-item">
                            <strong>{{ $evaluation->nom }} · {{ $evaluation->classe?->nom }}</strong>
                            <span>{{ $evaluation->matiere?->nom }} · {{ ucfirst($evaluation->type) }} · {{ $evaluation->date_evaluation?->format('d/m/Y') }}</span>
                            <div class="teacher-item-actions">
                                <a href="{{ route('notes.saisie', $evaluation) }}" class="btn btn-primary">Notes</a>
                            </div>
                        </div>
                    @empty
                        <div class="teacher-empty">Aucune évaluation trouvée.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="teacher-panel">
            <div class="teacher-panel-head">
                <div>
                    <div class="teacher-section-kicker">Semaine</div>
                    <h2>Créneaux programmés</h2>
                    <p>Aperçu rapide de vos prochains créneaux d’enseignement.</p>
                </div>
                <a href="{{ route('emplois-du-temps.semaine-enseignant') }}" class="btn btn-primary">Voir planning</a>
            </div>

            <div class="teacher-list">
                @forelse ($prochainsCours as $emploi)
                    <div class="teacher-list-item">
                        <strong>{{ ucfirst($emploi->jour) }} · {{ $emploi->heure_debut?->format('H:i') }} - {{ $emploi->heure_fin?->format('H:i') }}</strong>
                        <span>{{ $emploi->affectation?->classe?->nom }} · {{ $emploi->affectation?->matiere?->nom }}{{ $emploi->salle ? ' · Salle '.$emploi->salle : '' }}</span>
                    </div>
                @empty
                    <div class="teacher-empty">Aucun créneau programmé.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
