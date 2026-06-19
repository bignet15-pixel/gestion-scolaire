<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin annuel</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 20px;
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
        .terms {
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

        .summary,
        .terms {
            margin: 10px 0;
        }

        .summary th,
        .summary td,
        .terms th,
        .terms td {
            border: 1px solid #cbd5e1;
            padding: 6px;
        }

        .summary th,
        .terms th {
            background: #1e3a8a;
            color: #ffffff;
            text-align: left;
        }

        .number {
            text-align: right;
        }

        .decision {
            font-weight: bold;
            color: #1e3a8a;
        }

        .signature {
            margin-top: 34px;
            text-align: right;
            font-weight: bold;
        }

        .footer {
            margin-top: 18px;
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
        Bulletin annuel
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
            <td class="label">Décision :</td>
            <td class="decision">{{ $decision }}</td>
        </tr>
    </table>

    <table class="summary">
        <thead>
            <tr>
                <th>Moyenne annuelle</th>
                <th>Rang annuel</th>
                <th>Appréciation</th>
                <th>Décision</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="number">{{ number_format($moyenne_annuelle, 2, ',', ' ') }}/20</td>
                <td class="number">{{ $rang_annuel ?? '-' }}</td>
                <td>{{ $appreciation }}</td>
                <td class="decision">{{ $decision }}</td>
            </tr>
        </tbody>
    </table>

    <table class="terms">
        <thead>
            <tr>
                <th>Trimestre</th>
                <th class="number">Moyenne</th>
                <th class="number">Rang</th>
                <th class="number">Total pondéré</th>
                <th>Appréciation</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($trimestres as $bulletinTrimestre)
                <tr>
                    <td>{{ $bulletinTrimestre['trimestre']->nom }}</td>
                    <td class="number">{{ number_format($bulletinTrimestre['moyenne'], 2, ',', ' ') }}/20</td>
                    <td class="number">{{ $bulletinTrimestre['rang'] ?? '-' }}</td>
                    <td class="number">{{ number_format($bulletinTrimestre['total_pondere'], 2, ',', ' ') }}</td>
                    <td>{{ $bulletinTrimestre['appreciation'] }}</td>
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
