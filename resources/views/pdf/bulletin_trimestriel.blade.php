<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin trimestriel</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
        }

        .header {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .school-name {
            color: #1e3a8a;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .school-line {
            color: #374151;
            margin-top: 2px;
        }

        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
            padding: 6px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .meta,
        .summary,
        .notes {
            width: 100%;
            border-collapse: collapse;
        }

        .meta {
            margin-bottom: 10px;
        }

        .meta td {
            padding: 3px 0;
        }

        .label {
            width: 22%;
            font-weight: bold;
        }

        .summary {
            margin: 10px 0;
        }

        .summary th,
        .summary td,
        .notes th,
        .notes td {
            border: 1px solid #cbd5e1;
            padding: 5px;
        }

        .summary th,
        .notes th {
            background: #1e3a8a;
            color: #ffffff;
            text-align: left;
        }

        .notes td.number,
        .notes th.number,
        .summary td.number {
            text-align: right;
        }

        .decision {
            font-weight: bold;
            color: #1e3a8a;
        }

        .signature {
            margin-top: 28px;
            text-align: right;
            font-weight: bold;
        }

        .footer {
            margin-top: 16px;
            text-align: right;
            font-size: 9px;
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
        Bulletin trimestriel — {{ $trimestre->nom }}
    </div>

    <table class="meta">
        <tr>
            <td class="label">Année scolaire :</td>
            <td>{{ $inscription->anneeScolaire?->libelle ?? '-' }}</td>
            <td class="label">Classe :</td>
            <td>{{ $inscription->classe?->nom ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Élève :</td>
            <td>{{ $inscription->eleve?->nom }} {{ $inscription->eleve?->prenom }}</td>
            <td class="label">Matricule :</td>
            <td>{{ $inscription->eleve?->matricule ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Effectif :</td>
            <td>{{ $effectif }}</td>
            <td class="label">Enseignant principal :</td>
            <td>{{ $inscription->classe?->enseignantPrincipal?->name ?? '-' }}</td>
        </tr>
    </table>

    <table class="summary">
        <thead>
            <tr>
                <th>Moyenne</th>
                <th>Rang</th>
                <th>Total pondéré</th>
                <th>Total coefficients</th>
                <th>Appréciation</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="number">{{ $moyenne !== null ? number_format($moyenne, 2, ',', ' ') . '/20' : '-' }}</td>
                <td class="number">{{ $rang ?? '-' }}</td>
                <td class="number">{{ number_format($total_pondere, 2, ',', ' ') }}</td>
                <td class="number">{{ number_format($total_coefficients, 2, ',', ' ') }}</td>
                <td class="decision">{{ $appreciation }}</td>
            </tr>
        </tbody>
    </table>

    <table class="notes">
        <thead>
            <tr>
                <th>Matière</th>
                <th>Évaluation</th>
                <th>Type</th>
                <th class="number">Note</th>
                <th class="number">Barème</th>
                <th class="number">Note /20</th>
                <th class="number">Coef.</th>
                <th class="number">Points</th>
                <th>Appréciation</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lignes as $ligne)
                <tr>
                    <td>{{ $ligne['matiere'] }}</td>
                    <td>{{ $ligne['evaluation']->nom }}</td>
                    <td>{{ $ligne['type'] }}</td>
                    <td class="number">{{ number_format($ligne['note'], 2, ',', ' ') }}</td>
                    <td class="number">{{ number_format($ligne['bareme'], 2, ',', ' ') }}</td>
                    <td class="number">{{ number_format($ligne['note_sur_20'], 2, ',', ' ') }}</td>
                    <td class="number">{{ number_format($ligne['coefficient'], 2, ',', ' ') }}</td>
                    <td class="number">{{ number_format($ligne['points'], 2, ',', ' ') }}</td>
                    <td>{{ $ligne['appreciation'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature">
        La Direction
    </div>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>
