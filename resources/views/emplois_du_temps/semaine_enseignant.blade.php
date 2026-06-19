<x-app-layout>
    <div class="container">
        <div class="card">
            <h1>Emploi du temps hebdomadaire d’un enseignant</h1>

            <form action="{{ route('emplois-du-temps.semaine-enseignant') }}" method="GET" class="filter-form filter-form-large">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        {{-- Remplit la liste des annees scolaires. --}}
                        @foreach ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected((string) $selectedAnneeId === (string) $annee->id)>
                                {{ $annee->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Le gestionnaire peut choisir un enseignant; l'enseignant connecte garde son propre planning. --}}
                @if (auth()->user()->estGestionnaire())
                    <div class="form-group">
                        <label class="form-label">Enseignant</label>
                        <select name="enseignant_id" class="form-control">
                            {{-- Affiche les enseignants dans le tableau. --}}
                            @foreach ($enseignants as $enseignantOption)
                                <option value="{{ $enseignantOption->id }}" @selected((string) $selectedEnseignantId === (string) $enseignantOption->id)>
                                    {{ $enseignantOption->name }} — {{ $enseignantOption->matricule }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Semaine</label>
                    <input type="date" name="semaine" class="form-control" value="{{ $dateReference->format('Y-m-d') }}">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Afficher
                    </button>

                    <a href="{{ route('emplois-du-temps.semaine-enseignant') }}" class="btn">
                        Réinitialiser
                    </a>

                    <a
                        href="{{ route('emplois-du-temps.semaine-enseignant-pdf', [
                            'annee_scolaire_id' => $selectedAnneeId,
                            'enseignant_id' => $selectedEnseignantId,
                            'semaine' => $dateReference->format('Y-m-d'),
                        ]) }}"
                        class="btn btn-success"
                    >
                        Imprimer PDF
                    </a>

                    <a href="{{ auth()->user()->estGestionnaire() ? route('emplois-du-temps.index') : route('dashboard') }}" class="btn">
                        Retour
                    </a>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>
                {{-- Adapte le titre selon l'enseignant selectionne pour la semaine. --}}
                @if ($enseignant)
                    {{ $enseignant->name }} —
                    semaine du {{ $debutSemaine->format('d/m/Y') }}
                    au {{ $finSemaine->format('d/m/Y') }}
                @else
                    Aucun enseignant disponible
                @endif
            </h2>

            <table class="table planning-grid-table">
                <thead>
                    <tr>
                        <th class="planning-hour-column">Heure</th>
                        {{-- Affiche les jours de la semaine en colonnes. --}}
                        @foreach ($jours as $jour => $date)
                            <th>
                                {{ ucfirst($jour) }}
                                <span class="planning-date">{{ $date->format('d/m/Y') }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    {{-- Construit le planning avec les heures en lignes et les jours en colonnes. --}}
                    @foreach ($creneauxHoraires as $creneau)
                        {{-- Les pauses et coupures occupent toute la largeur du tableau. --}}
                        @if ($creneau['type'] !== 'cours')
                            <tr>
                                <td colspan="{{ count($jours) + 1 }}" class="{{ $creneau['type'] === 'pause' ? 'planning-break-cell' : 'planning-empty-cell' }}">
                                    @if ($creneau['type'] === 'pause')
                                        {{ $creneau['label'] }} — {{ $creneau['texte'] }}
                                    @else
                                        &nbsp;
                                    @endif
                                </td>
                            </tr>
                        {{-- Sinon, affichage des cours places dans les cellules jour + heure. --}}
                        @else
                            <tr>
                                <td class="planning-time-cell">{{ $creneau['label'] }}</td>

                                @foreach ($jours as $jour => $date)
                                    <td class="planning-grid-cell">
                                        {{-- Affiche les cours du jour qui chevauchent ce creneau horaire. --}}
                                        @forelse (($planningGrille[$creneau['id']][$jour] ?? []) as $item)
                                            <div class="planning-cell-item {{ $item['evaluation'] ? 'planning-cell-evaluation' : '' }}">
                                                {{-- Une evaluation remplace l'affichage du cours normal sur le meme horaire. --}}
                                                @if ($item['evaluation'])
                                                    <span>Évaluation : {{ $item['evaluation']->matiere?->nom ?? '-' }}</span>
                                                @else
                                                    <span>{{ $item['emploi']->affectation?->matiere?->nom ?? '-' }}</span>
                                                @endif

                                            </div>
                                        {{-- Cellule vide quand aucun cours n est place sur ce jour et ce creneau. --}}
                                        @empty
                                            <span class="planning-cell-empty">-</span>
                                        @endforelse
                                    </td>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>

            <div class="planning-mobile-list">
                @foreach ($jours as $jour => $date)
                    @php
                        $itemsBrutsJour = collect($planning[$jour] ?? []);
                        $evaluationsJour = $itemsBrutsJour->filter(fn ($item) => $item['evaluation']);
                        $itemsJour = $itemsBrutsJour
                            ->reject(function ($item) use ($evaluationsJour) {
                                if (! $item['emploi']) {
                                    return false;
                                }

                                $debutItem = strtotime($item['heure_debut']);
                                $finItem = strtotime($item['heure_fin']);

                                return $evaluationsJour->contains(function ($evaluationItem) use ($debutItem, $finItem) {
                                    $debutEvaluation = strtotime($evaluationItem['heure_debut']);
                                    $finEvaluation = strtotime($evaluationItem['heure_fin']);

                                    return $debutItem < $finEvaluation && $finItem > $debutEvaluation;
                                });
                            })
                            ->values();
                    @endphp

                    <section class="planning-mobile-day">
                        <div class="planning-mobile-day-header">
                            <h3>{{ ucfirst($jour) }}</h3>
                            <span>{{ $date->format('d/m/Y') }}</span>
                        </div>

                        <div class="planning-mobile-items">
                            @forelse ($itemsJour as $item)
                                @php
                                    $evaluation = $item['evaluation'];
                                    $emploi = $item['emploi'];
                                    $matiere = $evaluation?->matiere?->nom ?? $emploi?->affectation?->matiere?->nom ?? '-';
                                    $classeNom = $evaluation?->classe?->nom ?? $emploi?->affectation?->classe?->nom ?? '-';
                                    $salle = $emploi?->salle;
                                    $debutItem = strtotime($item['heure_debut']);
                                    $finItem = strtotime($item['heure_fin']);
                                    $horsCreneauHabituel = $evaluation && ! $itemsBrutsJour->contains(function ($autre) use ($debutItem, $finItem) {
                                        if (! $autre['emploi']) {
                                            return false;
                                        }

                                        $debutAutre = strtotime($autre['heure_debut']);
                                        $finAutre = strtotime($autre['heure_fin']);

                                        return $debutItem < $finAutre && $finItem > $debutAutre;
                                    });
                                @endphp

                                <article class="planning-mobile-item {{ $evaluation ? 'planning-mobile-evaluation' : '' }}">
                                    <div class="planning-mobile-item-head">
                                        <span class="planning-mobile-time">
                                            {{ $item['heure_debut'] }} - {{ $item['heure_fin'] }}
                                        </span>

                                        <div class="planning-mobile-badges">
                                            @if ($evaluation)
                                                <span class="planning-mobile-badge planning-mobile-badge-evaluation">
                                                    Évaluation
                                                </span>
                                            @endif

                                            @if ($horsCreneauHabituel)
                                                <span class="planning-mobile-badge planning-mobile-badge-warning">
                                                    Hors créneau habituel
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <strong class="planning-mobile-subject">{{ $matiere }}</strong>

                                    <div class="planning-mobile-meta">
                                        <span>Classe</span>
                                        <strong>{{ $classeNom }}</strong>
                                    </div>

                                    @if ($salle)
                                        <div class="planning-mobile-meta">
                                            <span>Salle</span>
                                            <strong>{{ $salle }}</strong>
                                        </div>
                                    @endif
                                </article>
                            @empty
                                <div class="planning-mobile-empty">
                                    Aucun créneau prévu.
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>

            <h2 class="planning-detail-title">Détails des créneaux</h2>

            <table class="table planning-detail-table">
                <thead>
                    <tr>
                        <th>Classe</th>
                        <th>Matière</th>
                        <th>Enseignant</th>
                        <th>Salle</th>
                        <th>Coefficient</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Regroupe les creneaux presents dans la semaine avec classe, matiere, enseignant, salle et coefficient. --}}
                    @forelse ($detailsPlanning as $detail)
                        <tr>
                            <td>{{ $detail['classe'] }}</td>
                            <td>{{ $detail['matiere'] }}</td>
                            <td>{{ $detail['enseignant'] }}</td>
                            <td>{{ $detail['salle'] }}</td>
                            <td>{{ $detail['coefficient'] !== null ? number_format((float) $detail['coefficient'], 2, ',', ' ') : '-' }}</td>
                        </tr>
                    {{-- Message affiche quand aucun cours n est present dans la semaine. --}}
                    @empty
                        <tr>
                            <td colspan="5">Aucun détail à afficher.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
