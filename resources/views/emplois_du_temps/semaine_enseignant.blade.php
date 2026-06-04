<x-app-layout>
{{-- Vue Blade : resources/views/emplois_du_temps/semaine_enseignant.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Emploi du temps hebdomadaire d’un enseignant</h1>

            <form action="{{ route('emplois-du-temps.semaine-enseignant') }}" method="GET">
                {{-- Condition : auth()->user()->estGestionnaire(). --}}
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

                <button type="submit" class="btn btn-primary">
                    Afficher
                </button>

                <a href="{{ route('emplois-du-temps.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>

        <div class="card">
            <h2>
                {{-- Condition : $enseignant. --}}
                @if ($enseignant)
                    {{ $enseignant->name }} —
                    semaine du {{ $debutSemaine->format('d/m/Y') }}
                    au {{ $finSemaine->format('d/m/Y') }}
                {{-- Sinon, affichage de l alternative prevue. --}}
                @else
                    Aucun enseignant disponible
                @endif
            </h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Jour</th>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Classe</th>
                        <th>Contenu</th>
                        <th>Salle</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Organise l affichage du planning par jour. --}}
                    @foreach ($planning as $jour => $creneaux)
                        {{-- Affiche les cours du jour, ou le message vide si aucun cours n est prevu. --}}
                        @forelse ($creneaux as $item)
                            <tr>
                                <td>{{ ucfirst($jour) }}</td>
                                <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
                                <td>{{ $item['heure_debut'] }} - {{ $item['heure_fin'] }}</td>
                                <td>{{ $item['emploi']->affectation->classe->nom }}</td>

                                <td>
                                    {{-- Condition : $item['evaluation']. --}}
                                    @if ($item['evaluation'])
                                        <strong>Évaluation : {{ $item['evaluation']->nom }}</strong>
                                        <br>
                                        Type : {{ $item['evaluation']->type }}
                                        <br>
                                        Matière : {{ $item['evaluation']->matiere?->nom }}
                                    {{-- Sinon, affichage de l alternative prevue. --}}
                                    @else
                                        {{ $item['emploi']->affectation->matiere->nom }}
                                    @endif
                                </td>

                                <td>{{ $item['emploi']->salle ?? '-' }}</td>
                            </tr>
                        {{-- Message affiche quand la liste est vide. --}}
                        @empty
                            <tr>
                                <td>{{ ucfirst($jour) }}</td>
                                <td>{{ $jours[$jour]->format('d/m/Y') }}</td>
                                <td colspan="4">Aucun créneau</td>
                            </tr>
                        @endforelse
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>