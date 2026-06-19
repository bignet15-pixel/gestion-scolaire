<x-app-layout>
{{-- Vue Blade : resources/views/classes/mes_classes.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Mes classes</h1>

            <form action="{{ route('enseignant.classes.index') }}" method="GET" class="filter-form filter-form-large">
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

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Afficher
                    </button>

                    <a href="{{ route('enseignant.classes.index') }}" class="btn">
                        Réinitialiser
                    </a>
                </div>
            </form>

            <table class="table">
                <thead>
                    <tr>
                        <th>Année scolaire</th>
                        <th>Niveau</th>
                        <th>Classe</th>
                        <th>Enseignant principal</th>
                        <th>Élèves</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Affiche les classes affectees a l enseignant pour l annee choisie, ou le message vide. --}}
                    @forelse ($classes as $classe)
                        <tr>
                            <td>{{ $classe->anneeScolaire?->libelle }}</td>
                            <td>{{ $classe->niveau }}</td>
                            <td>{{ $classe->nom }}</td>
                            <td>{{ $classe->enseignantPrincipal?->name ?? 'Non affecté' }}</td>
                            <td>{{ $classe->inscriptions_count }}</td>

                            <td>
                                <a href="{{ route('enseignant.classes.show', $classe) }}" class="btn btn-success">
                                    Détail
                                </a>

                                <a href="{{ route('enseignant.classes.eleves-pdf', $classe) }}" class="btn btn-primary">
                                    PDF élèves
                                </a>
                            </td>
                        </tr>
                    {{-- Message affiche quand aucune classe n est affectee pour l annee choisie. --}}
                    @empty
                        <tr>
                            <td colspan="6">
                                Aucune classe affectée pour cette année scolaire.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
