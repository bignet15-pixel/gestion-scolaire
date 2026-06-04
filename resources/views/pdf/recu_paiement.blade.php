{{-- Vue Blade : resources/views/pdf/recu_paiement.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de paiement</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 0;
            padding: 0;
        }

        .receipt {
            border: 1.5px solid #1e3a8a;
            padding: 16px 18px;
        }

        .top {
            width: 100%;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .logo-box {
            width: 70px;
            height: 70px;
            border: 1.5px solid #1e3a8a;
            text-align: center;
            vertical-align: middle;
            font-size: 22px;
            font-weight: bold;
            color: #1e3a8a;
        }

        .school {
            padding-left: 14px;
        }

        .school-name {
            font-size: 22px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .school-line {
            font-size: 11px;
            color: #374151;
            margin-bottom: 2px;
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            color: #111827;
            margin: 12px 0;
            padding: 6px 0;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .section {
            border: 1px solid #cbd5e1;
            margin-bottom: 9px;
        }

        .section-title {
            background: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 8px;
            font-size: 11px;
        }

        .section-content {
            padding: 7px 9px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 3px 0;
            vertical-align: top;
        }

        .label {
            width: 35%;
            font-weight: bold;
            color: #111827;
        }

        .value {
            width: 65%;
            color: #1f2937;
        }

        .money-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        .money-table th {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 6px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }

        .money-table td {
            border: 1px solid #d1d5db;
            padding: 7px 6px;
            font-size: 12px;
        }

        .amount-paid {
            color: #15803d;
            font-weight: bold;
        }

        .amount-rest {
            color: #dc2626;
            font-weight: bold;
        }

        .signatures {
            width: 100%;
            margin-top: 30px;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            padding-top: 18px;
        }

        .signature-line {
            width: 170px;
            border-top: 1px solid #111827;
            margin: 0 auto 5px auto;
        }

        .signature-label {
            font-size: 11px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="receipt">
        <table class="top">
            <tr>
                <td class="logo-box">
                    BZ
                </td>

                <td class="school">
                    <div class="school-name">
                        {{ config('ecole.nom') }}
                    </div>

                    <div class="school-line">
                        {{ config('ecole.devise') }}
                    </div>

                    <div class="school-line">
                        Contact : {{ config('ecole.contact') }}
                    </div>

                    <div class="school-line">
                        Directeur : {{ config('ecole.directeur') }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="title">
            Reçu de paiement
        </div>

        <div class="section">
            <div class="section-title">Informations du reçu</div>

            <div class="section-content">
                <table>
                    <tr>
                        <td class="label">Numéro du reçu :</td>
                        <td class="value">{{ $paiement->numero_paiement }}</td>
                    </tr>

                    <tr>
                        <td class="label">Date du paiement :</td>
                        <td class="value">{{ $paiement->date_paiement?->format('d/m/Y') ?? '-' }}</td>
                    </tr>

                    <tr>
                        <td class="label">Mode de paiement :</td>
                        <td class="value">{{ ucfirst($paiement->mode_paiement) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Informations de l’élève</div>

            <div class="section-content">
                <table>
                    <tr>
                        <td class="label">Matricule :</td>
                        <td class="value">{{ $paiement->inscription?->eleve?->matricule ?? '-' }}</td>
                    </tr>

                    <tr>
                        <td class="label">Nom et prénom :</td>
                        <td class="value">
                            {{ $paiement->inscription?->eleve?->nom ?? '-' }}
                            {{ $paiement->inscription?->eleve?->prenom ?? '' }}
                        </td>
                    </tr>

                    <tr>
                        <td class="label">Classe :</td>
                        <td class="value">{{ $paiement->inscription?->classe?->nom ?? '-' }}</td>
                    </tr>

                    <tr>
                        <td class="label">Année scolaire :</td>
                        <td class="value">{{ $paiement->inscription?->anneeScolaire?->libelle ?? '-' }}</td>
                    </tr>

                    <tr>
                        <td class="label">Contact parent :</td>
                        <td class="value">{{ $paiement->contact_parent ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Situation financière</div>

            <div class="section-content">
                <table class="money-table">
                    <thead>
                        <tr>
                            <th>Frais attendus</th>
                            <th>Montant payé</th>
                            <th>Total payé</th>
                            <th>Reste à payer</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>
                                {{ number_format($paiement->inscription?->frais_attendu ?? 0, 0, ',', ' ') }} FCFA
                            </td>

                            <td class="amount-paid">
                                {{ number_format($paiement->montant ?? 0, 0, ',', ' ') }} FCFA
                            </td>

                            <td>
                                {{ number_format($paiement->inscription?->totalPaye() ?? 0, 0, ',', ' ') }} FCFA
                            </td>

                            <td class="amount-rest">
                                {{ number_format($paiement->inscription?->resteAPayer() ?? 0, 0, ',', ' ') }} FCFA
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Gestionnaire</div>

            <div class="section-content">
                <table>
                    <tr>
                        <td class="label">Nom :</td>
                        <td class="value">{{ $paiement->gestionnaire?->name ?? '-' }}</td>
                    </tr>

                    <tr>
                        <td class="label">Contact :</td>
                        <td class="value">{{ $paiement->contact_gestionnaire ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="signatures">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-label">Signature du parent</div>
                </td>

                <td>
                    <div class="signature-line"></div>
                    <div class="signature-label">Signature du gestionnaire</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>