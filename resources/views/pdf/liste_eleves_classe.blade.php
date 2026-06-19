{{-- Vue Blade : resources/views/pdf/liste_eleves_classe.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des élèves</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 22px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        .header {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .school-name {
            color: #1e3a8a;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .school-line {
            color: #374151;
            margin-top: 2px;
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 14px 0;
            padding: 7px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .meta {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        .meta td {
            padding: 4px 0;
        }

        .label {
            width: 28%;
            font-weight: bold;
        }

        table.students {
            width: 100%;
            border-collapse: collapse;
        }

        .students th {
            background: #1e3a8a;
            color: #ffffff;
            padding: 7px 6px;
            text-align: left;
            border: 1px solid #1e3a8a;
        }

        .students td {
            padding: 6px;
            border: 1px solid #cbd5e1;
        }

        .footer {
            margin-top: 26px;
            text-align: right;
            font-size: 10px;
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
        Liste des élèves de {{$classe->nom}}
    </div>

    <table class="meta">
        <tr>
            <td class="label">Année scolaire :</td>
            <td>{{ $classe->anneeScolaire?->libelle ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Classe :</td>
            <td>{{ $classe->nom }}</td>
        </tr>
        <tr>
            <td class="label">Niveau :</td>
            <td>{{ $classe->niveau }}</td>
        </tr>
        <tr>
            <td class="label">Enseignant principal :</td>
            <td>{{ $classe->enseignantPrincipal?->name ?? 'Non affecté' }}</td>
        </tr>
        <tr>
            <td class="label">Effectif :</td>
            <td>{{ $inscriptions->count() }}</td>
        </tr>
    </table>

    <table class="students">
        <thead>
            <tr>
                <th style="width: 8%;">N°</th>
                <th style="width: 22%;">Matricule</th>
                <th style="width: 28%;">Nom</th>
                <th style="width: 28%;">Prénom</th>
            </tr>
        </thead>

        <tbody>
            {{-- Affiche les eleves inscrits dans la classe pour produire la liste PDF. --}}
            @forelse ($inscriptions as $index => $inscription)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $inscription->eleve?->matricule ?? '-' }}</td>
                    <td>{{ $inscription->eleve?->nom ?? '-' }}</td>
                    <td>{{ $inscription->eleve?->prenom ?? '-' }}</td>
                </tr>
            {{-- Message affiche quand aucune inscription n existe dans la classe. --}}
            @empty
                <tr>
                    <td colspan="5">Aucun élève inscrit dans cette classe.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>
