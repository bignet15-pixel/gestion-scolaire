<x-app-layout>
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Espace parent</div>
                <h1>{{ $eleve->nom }} {{ $eleve->prenom }}</h1>
                <p>Fiche complète : notes, résultats, bulletins, paiements, absences, sanctions et réinscription.</p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('dashboard') }}" class="btn">Retour</a>
            </div>
        </div>

        <div class="student-profile-card">
            <div class="student-photo-box">
                @if ($eleve->photo)
                    <img src="{{ asset('storage/' . $eleve->photo) }}" alt="Photo élève">
                @else
                    <div class="student-photo-placeholder">
                        {{ strtoupper(substr($eleve->nom, 0, 1)) }}{{ strtoupper(substr($eleve->prenom, 0, 1)) }}
                    </div>
                @endif
            </div>

            <div class="student-info">
                <div class="student-name">{{ $eleve->nom }} {{ $eleve->prenom }}</div>
                <div class="student-matricule">Matricule : {{ $eleve->matricule }}</div>

                <div class="profile-grid">
                    <div class="profile-row">
                        <span>Sexe</span>
                        <strong>{{ $eleve->sexe }}</strong>
                    </div>
                    <div class="profile-row">
                        <span>Date de naissance</span>
                        <strong>{{ $eleve->date_naissance?->format('d/m/Y') ?? '-' }}</strong>
                    </div>
                    <div class="profile-row">
                        <span>Lieu de naissance</span>
                        <strong>{{ $eleve->lieu_naissance ?? '-' }}</strong>
                    </div>
                    <div class="profile-row">
                        <span>Contact parent</span>
                        <strong>{{ $eleve->contact_parent ?? '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Filtres d’historique</h2>

            <form method="GET" action="{{ route('parent.eleves.show', $eleve) }}" class="filter-form filter-form-large">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        @foreach ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected((string) $selectedAnneeId === (string) $annee->id)>
                                {{ $annee->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Trimestre</label>
                    <select name="trimestre_id" class="form-control">
                        <option value="">Tous</option>
                        @foreach ($trimestres as $trimestre)
                            <option value="{{ $trimestre->id }}" @selected((string) $selectedTrimestreId === (string) $trimestre->id)>
                                {{ $trimestre->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Type assiduité</label>
                    <select name="type" class="form-control">
                        <option value="">Tous</option>
                        <option value="absence" @selected($selectedType === 'absence')>Absences</option>
                        <option value="retard" @selected($selectedType === 'retard')>Retards</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="{{ route('parent.eleves.show', $eleve) }}" class="btn">Réinitialiser</a>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Inscription et frais de l’année sélectionnée</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Année</th>
                            <th>Classe</th>
                            <th>Frais attendus</th>
                            <th>Payé officiel</th>
                            <th>Reste officiel</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inscriptionsFiltrees as $inscription)
                            @php
                                $totalPaye = $inscription->paiements->sum('montant');
                                $reste = max(0, (float) $inscription->frais_attendu - (float) $totalPaye);
                            @endphp
                            <tr>
                                <td>{{ $inscription->anneeScolaire?->libelle ?? '-' }}</td>
                                <td>{{ $inscription->classe?->nom ?? '-' }}</td>
                                <td>{{ number_format($inscription->frais_attendu, 0, ',', ' ') }} FCFA</td>
                                <td>{{ number_format($totalPaye, 0, ',', ' ') }} FCFA</td>
                                <td>{{ number_format($reste, 0, ',', ' ') }} FCFA</td>
                                <td>{{ $inscription->statut }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">Aucune inscription pour l’année sélectionnée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" id="notes">
            <h2>Notes</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Trimestre</th>
                            <th>Matière</th>
                            <th>Évaluation</th>
                            <th>Note</th>
                            <th>Barème</th>
                            <th>Appréciation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($notes as $note)
                            <tr>
                                <td>{{ $note->evaluation?->date_evaluation?->format('d/m/Y') ?? '-' }}</td>
                                <td>{{ $note->evaluation?->trimestre?->nom ?? '-' }}</td>
                                <td>{{ $note->evaluation?->matiere?->nom ?? '-' }}</td>
                                <td>{{ $note->evaluation?->nom ?? '-' }}</td>
                                <td>{{ $note->valeur }}</td>
                                <td>{{ $note->evaluation?->bareme ?? '-' }}</td>
                                <td>{{ $note->appreciation ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">Aucune note pour les filtres sélectionnés.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" id="resultats">
            <h2>Résultats trimestriels</h2>

            @forelse ($resultatsTrimestriels as $resultat)
                <div class="trimester-result">
                    <div class="trimester-header">
                        <div>
                            <h3>{{ $resultat['trimestre']->nom }}</h3>
                            <p>{{ $resultat['trimestre']->libelleStatutPedagogique() }}</p>
                        </div>

                        @if ($resultat['disponible'] && $inscriptionPrincipale)
                            <a href="{{ route('parent.bulletins.trimestriel', [$inscriptionPrincipale, $resultat['trimestre']]) }}" class="btn btn-primary">
                                Télécharger PDF
                            </a>
                        @endif
                    </div>

                    @if ($resultat['disponible'])
                        <div class="trimester-summary-grid">
                            <div class="trimester-summary-item">
                                <span>Moyenne</span>
                                <strong>{{ $resultat['data']['moyenne'] }}/20</strong>
                            </div>
                            <div class="trimester-summary-item">
                                <span>Rang</span>
                                <strong>{{ $resultat['data']['rang'] ? $resultat['data']['rang'].'e' : '-' }}</strong>
                            </div>
                            <div class="trimester-summary-item">
                                <span>Effectif</span>
                                <strong>{{ $resultat['data']['effectif'] }}</strong>
                            </div>
                            <div class="trimester-summary-item">
                                <span>Appréciation</span>
                                <strong>{{ $resultat['data']['appreciation'] }}</strong>
                            </div>
                            <div class="trimester-summary-item">
                                <span>Retenues visibles</span>
                                <strong>{{ $resultat['data']['total_points_en_moins_visibles'] ?? 0 }}</strong>
                            </div>
                        </div>
                    @else
                        <div class="result-unpublished-box">
                            {{ $resultat['message'] }}
                        </div>
                    @endif
                </div>
            @empty
                <p>Aucun trimestre pour l’année sélectionnée.</p>
            @endforelse
        </div>

        <div class="card" id="bulletins">
            <h2>Bulletin annuel</h2>

            @if ($bulletinAnnuel['disponible'] && $inscriptionPrincipale)
                <div class="annual-result-card">
                    <div class="annual-result-head">
                        <div>
                            <h3>Résultat annuel</h3>
                            <p>Décision : {{ $bulletinAnnuel['data']['decision'] }}</p>
                        </div>
                        <div class="annual-result-actions">
                            <a href="{{ route('parent.bulletins.annuel', $inscriptionPrincipale) }}" class="btn btn-primary">
                                Télécharger bulletin annuel
                            </a>
                        </div>
                    </div>

                    <div class="annual-result-grid">
                        <div>
                            <span>Moyenne annuelle</span>
                            <strong>{{ $bulletinAnnuel['data']['moyenne_annuelle'] }}/20</strong>
                        </div>
                        <div>
                            <span>Rang annuel</span>
                            <strong>{{ $bulletinAnnuel['data']['rang_annuel'] ? $bulletinAnnuel['data']['rang_annuel'].'e' : '-' }}</strong>
                        </div>
                        <div>
                            <span>Appréciation</span>
                            <strong>{{ $bulletinAnnuel['data']['appreciation'] }}</strong>
                        </div>
                    </div>
                </div>
            @else
                <div class="result-unpublished-box">
                    {{ $bulletinAnnuel['message'] }}
                </div>
            @endif
        </div>

        <div class="card" id="paiements">
            <h2>Paiements officiels</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Numéro</th>
                            <th>Montant</th>
                            <th>Mode</th>
                            <th>Reçu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paiements as $paiement)
                            <tr>
                                <td>{{ $paiement->date_paiement?->format('d/m/Y') }}</td>
                                <td>{{ $paiement->numero_paiement }}</td>
                                <td>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                                <td>{{ str_replace('_', ' ', $paiement->mode_paiement) }}</td>
                                <td>
                                    <a href="{{ route('parent.paiements.recu', $paiement) }}" class="btn">
                                        Télécharger
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">Aucun paiement officiel.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" id="paiements-declares">
            <h2>Déclarer un paiement</h2>

            @if ($inscriptionPrincipale && max(0, (float) $inscriptionPrincipale->frais_attendu - (float) $inscriptionPrincipale->paiements->sum('montant')) > 0)
                <form action="{{ route('parent.paiements-declares.store', $inscriptionPrincipale) }}" method="POST" enctype="multipart/form-data" class="form-grid">
                    @csrf

                    <div class="form-group">
                        <label class="form-label">Montant</label>
                        <input type="number" name="montant" min="1" step="1" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mode de paiement</label>
                        <select name="mode_paiement" class="form-control" required>
                            <option value="mobile_money">Mobile money</option>
                            <option value="especes">Espèces</option>
                            <option value="virement">Virement</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Référence transaction</label>
                        <input type="text" name="reference_transaction" class="form-control" placeholder="Ex : OM-123456">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Preuve</label>
                        <input type="file" name="preuve_paiement" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    </div>

                    <div class="form-actions form-group-full">
                        <button type="submit" class="btn btn-primary">Envoyer la déclaration</button>
                    </div>
                </form>
            @else
                <p>Aucun reste à payer pour l’inscription sélectionnée, ou aucune inscription sélectionnée.</p>
            @endif

            <hr>

            <h3>Déclarations envoyées</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Mode</th>
                            <th>Référence</th>
                            <th>Statut</th>
                            <th>Preuve</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paiementsDeclares as $paiementDeclare)
                            <tr>
                                <td>{{ $paiementDeclare->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ number_format($paiementDeclare->montant, 0, ',', ' ') }} FCFA</td>
                                <td>{{ str_replace('_', ' ', $paiementDeclare->mode_paiement) }}</td>
                                <td>{{ $paiementDeclare->reference_transaction ?? '-' }}</td>
                                <td>{{ $paiementDeclare->libelleStatut() }}</td>
                                <td>
                                    @if ($paiementDeclare->preuve_paiement)
                                        <a href="{{ route('parent.paiements-declares.preuve', $paiementDeclare) }}" class="btn">Voir</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">Aucune déclaration de paiement.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" id="assiduite">
            <h2>Absences et retards</h2>

            <div class="table-responsive">
                <table class="table assiduite-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Période</th>
                            <th>Motif école</th>
                            <th>Statut</th>
                            <th>Justification parent</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($absencesRetards as $absenceRetard)
                            <tr>
                                <td>{{ $absenceRetard->libelleType() }}</td>
                                <td>{{ $absenceRetard->date_debut?->format('d/m/Y') }}</td>
                                <td>{{ $absenceRetard->libellePeriode() }}</td>
                                <td>{{ $absenceRetard->motif ?? '-' }}</td>
                                <td>{{ $absenceRetard->libelleStatut() }}</td>
                                <td>
                                    @if ($absenceRetard->justificationParentale)
                                        {{ $absenceRetard->justificationParentale->libelleStatut() }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if (! $absenceRetard->justificationParentale && $absenceRetard->statut !== 'justifiee')
                                        <a href="{{ route('parent.justifications.create', $absenceRetard) }}" class="btn btn-primary btn-sm">Justifier</a>
                                    @elseif ($absenceRetard->justificationParentale?->piece_jointe)
                                        <a href="{{ route('parent.justifications.piece', $absenceRetard->justificationParentale) }}" class="btn btn-sm">Voir pièce</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">Aucune absence ou retard pour les filtres sélectionnés.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" id="sanctions">
            <h2>Sanctions</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sanction</th>
                            <th>Trimestre</th>
                            <th>Motif</th>
                            <th>Effet</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sanctions as $sanctionAppliquee)
                            <tr>
                                <td>{{ $sanctionAppliquee->sanction?->nom ?? '-' }}</td>
                                <td>{{ $sanctionAppliquee->trimestre?->nom ?? '-' }}</td>
                                <td>{{ $sanctionAppliquee->motif ?? '-' }}</td>
                                <td>
                                    {{ $sanctionAppliquee->type_effet }}
                                    @if ($sanctionAppliquee->valeur_effet !== null)
                                        : {{ number_format($sanctionAppliquee->valeur_effet, 2, ',', ' ') }}
                                    @endif
                                </td>
                                <td>{{ $sanctionAppliquee->statut }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">Aucune sanction pour les filtres sélectionnés.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" id="reinscription">
            <h2>Réinscription</h2>

            @if (($reinscriptionOption['possible'] ?? false) && $inscriptionPrincipale)
                <div class="alert alert-info">
                    Décision système : {{ str_replace('_', ' ', $reinscriptionOption['decision_systeme']) }}.
                    Année suivante : {{ $reinscriptionOption['nouvelle_annee']->libelle }}.
                </div>

                <form action="{{ route('parent.demandes-reinscription.store', $eleve) }}" method="POST" class="form-grid">
                    @csrf
                    <input type="hidden" name="ancienne_inscription_id" value="{{ $inscriptionPrincipale->id }}">

                    <div class="form-group">
                        <label class="form-label">Classe demandée</label>
                        <select name="classe_demandee_id" class="form-control" required>
                            @foreach ($reinscriptionOption['classes_disponibles'] as $classe)
                                <option value="{{ $classe->id }}">
                                    {{ $classe->nom }} - {{ number_format($classe->frais_scolarite, 0, ',', ' ') }} FCFA
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group form-group-full">
                        <label class="form-label">Commentaire parent</label>
                        <textarea name="commentaire_parent" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-actions form-group-full">
                        <button type="submit" class="btn btn-primary">Envoyer la demande</button>
                    </div>
                </form>
            @else
                <div class="result-unpublished-box">
                    {{ $reinscriptionOption['raison'] ?? 'Réinscription non disponible.' }}
                </div>
            @endif

            <hr>

            <h3>Demandes envoyées</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Année suivante</th>
                            <th>Classe demandée</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Commentaire école</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($demandesReinscription as $demande)
                            <tr>
                                <td>{{ $demande->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $demande->nouvelleAnneeScolaire?->libelle ?? '-' }}</td>
                                <td>{{ $demande->classeDemandee?->nom ?? '-' }}</td>
                                <td>{{ $demande->libelleTypeDemande() }}</td>
                                <td>{{ $demande->libelleStatut() }}</td>
                                <td>{{ $demande->commentaire_gestionnaire ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">Aucune demande de réinscription.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
