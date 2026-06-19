<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planning enseignant</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 12px 14px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.6px;
            line-height: 1.15;
            color: #111827;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 5px;
            margin-bottom: 6px;
        }

        .school-name {
            color: #1e3a8a;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .school-line {
            color: #374151;
            font-size: 8px;
            margin-top: 1px;
        }

        .title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 6px 0;
            padding: 4px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .meta {
            width: 100%;
            margin-bottom: 6px;
            border-collapse: collapse;
        }

        .meta td {
            padding: 1px 0;
        }

        .label {
            width: 16%;
            font-weight: bold;
        }

        table.planning {
            width: 100%;
            border-collapse: collapse;
        }

        .planning th {
            background: #1e3a8a;
            color: #ffffff;
            padding: 3px;
            font-size: 8.4px;
            text-align: center;
            border: 1px solid #1e3a8a;
        }

        .planning td {
            padding: 3px;
            font-size: 8.2px;
            line-height: 1.15;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }

        .evaluation {
            color: #1e3a8a;
            font-weight: bold;
        }

        .time-cell {
            width: 12%;
            background: #eff6ff;
            color: #172554;
            font-weight: bold;
            white-space: nowrap;
        }

        .break-cell {
            background: #fef3c7;
            color: #92400e;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }

        .empty-cell {
            background: #f8fafc;
            color: #f8fafc;
            padding: 1px 3px;
            line-height: 4px;
        }

        .cell-item {
            background: transparent;
            border: 0;
            padding: 0;
            margin-bottom: 1px;
        }

        .details-title {
            margin-top: 7px;
            margin-bottom: 3px;
            font-size: 10.5px;
            font-weight: bold;
            color: #172554;
        }

        .footer {
            margin-top: 7px;
            text-align: right;
            font-size: 7.5px;
            color: #4b5563;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="school-name">{{ config('ecole.nom') }}</div>
        <div class="school-line">{{ config('ecole.devise') }}</div>
        <div class="school-line">Contact : {{ config('ecole.contact') }}</div>
    </div>

    <div class="title">
        Planning hebdomadaire enseignant
    </div>

    <table class="meta">
        <tr>
            <td class="label">Année scolaire :</td>
            <td>
                {{ $annees->firstWhere('id', $selectedAnneeId)?->libelle ?? '-' }}
            </td>
            <td class="label">Enseignant :</td>
            <td>{{ $enseignant?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Semaine :</td>
            <td>Du {{ $debutSemaine->format('d/m/Y') }} au {{ $finSemaine->format('d/m/Y') }}</td>
            <td class="label">Matricule :</td>
            <td>{{ $enseignant?->matricule ?? '-' }}</td>
        </tr>
    </table>

    <table class="planning">
        <thead>
            <tr>
                <th style="width: 12%;">Heure</th>
                @foreach ($jours as $jour => $date)
                    <th>
                        {{ ucfirst($jour) }}<br>
                        {{ $date->format('d/m/Y') }}
                    </th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            {{-- Reprend la grille horaire de l'ecran pour garder le PDF coherent. --}}
            @foreach ($creneauxHoraires as $creneau)
                @if ($creneau['type'] !== 'cours')
                    <tr>
                        <td colspan="{{ count($jours) + 1 }}" class="{{ $creneau['type'] === 'pause' ? 'break-cell' : 'empty-cell' }}">
                            @if ($creneau['type'] === 'pause')
                                {{ $creneau['label'] }} — {{ $creneau['texte'] }}
                            @else
                                &nbsp;
                            @endif
                        </td>
                    </tr>
                @else
                    <tr>
                        <td class="time-cell">{{ $creneau['label'] }}</td>

                        @foreach ($jours as $jour => $date)
                            <td>
                                @forelse (($planningGrille[$creneau['id']][$jour] ?? []) as $item)
                                    <div class="cell-item">
                                        @if ($item['evaluation'])
                                            <span class="evaluation">Évaluation</span><br>
                                            {{ $item['evaluation']->matiere?->nom ?? '-' }}<br>
                                        @else
                                            {{ $item['emploi']->affectation?->matiere?->nom ?? '-' }}<br>
                                        @endif

                                    </div>
                                @empty
                                    -
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="details-title">Détails des créneaux</div>

    <table class="planning">
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
            @forelse ($detailsPlanning as $detail)
                <tr>
                    <td>{{ $detail['classe'] }}</td>
                    <td>{{ $detail['matiere'] }}</td>
                    <td>{{ $detail['enseignant'] }}</td>
                    <td>{{ $detail['salle'] }}</td>
                    <td>{{ $detail['coefficient'] !== null ? number_format((float) $detail['coefficient'], 2, ',', ' ') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Aucun détail à afficher.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>
