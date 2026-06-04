<x-app-layout>
{{-- Vue Blade : resources/views/notes/saisie.blade.php --}}
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Saisie des notes</div>

                <h1>{{ $evaluation->nom }}</h1>

                <p>
                    Saisie des notes pour la classe
                    <strong>{{ $evaluation->classe?->nom ?? '-' }}</strong>,
                    matière
                    <strong>{{ $evaluation->matiere?->nom ?? '-' }}</strong>.
                </p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('evaluations.index') }}" class="btn">
                    Retour
                </a>

                <a href="{{ route('evaluations.show', $evaluation) }}" class="btn btn-success">
                    Détail évaluation
                </a>
            </div>
        </div>

        <div class="note-summary-grid">
            <div class="note-summary-card">
                <span>Type</span>
                <strong>{{ ucfirst($evaluation->type) }}</strong>
            </div>

            <div class="note-summary-card">
                <span>Classe</span>
                <strong>{{ $evaluation->classe?->nom ?? '-' }}</strong>
            </div>

            <div class="note-summary-card">
                <span>Matière</span>
                <strong>{{ $evaluation->matiere?->nom ?? '-' }}</strong>
            </div>

            <div class="note-summary-card">
                <span>Trimestre</span>
                <strong>{{ $evaluation->trimestre?->nom ?? '-' }}</strong>
            </div>

            <div class="note-summary-card">
                <span>Barème</span>
                <strong>{{ number_format($evaluation->bareme, 2, ',', ' ') }}</strong>
            </div>

            <div class="note-summary-card">
                <span>Coefficient</span>
                <strong>{{ number_format($evaluation->coefficient, 2, ',', ' ') }}</strong>
            </div>
        </div>

        <div class="card note-entry-card">
            <div class="note-entry-header">
                <div>
                    <h2>Liste des élèves</h2>

                    <p>
                        Seuls les élèves inscrits dans la classe et l’année scolaire de cette évaluation sont affichés.
                    </p>
                </div>

                <div class="note-entry-bareme">
                    Note sur {{ number_format($evaluation->bareme, 2, ',', ' ') }}
                </div>
                
            </div>

            {{-- Condition : $errors->any(). --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    {{-- Affiche les messages d erreur de validation. --}}
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            {{-- Condition : session('success'). --}}
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('notes.enregistrer', $evaluation) }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf

                <table class="table note-table">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Élève</th>
                            <th>Note / {{ number_format($evaluation->bareme, 2, ',', ' ') }}</th>
                            <th>Appréciation actuelle</th>
                        </tr>
                    </thead>

                    <tbody>
                        {{-- Affiche les inscriptions dans le tableau, ou le message vide si aucun resultat n existe. --}}
                        @forelse ($inscriptions as $inscription)
                            {{-- Preparation des donnees de la vue. --}}
                            @php
                                $noteExistante = $inscription->notes->first();
                                $appreciation = $noteExistante?->appreciation;
                            @endphp

                            <tr>
                                <td>
                                    <span class="student-code">
                                        {{ $inscription->eleve?->matricule ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="note-student-name">
                                        {{ $inscription->eleve?->nom ?? '-' }}
                                        {{ $inscription->eleve?->prenom ?? '' }}
                                    </div>

                                    
                                </td>

                                <td>
                                <input
                                    type="number"
                                    name="notes[{{ $inscription->id }}][valeur]"
                                    class="form-control note-input js-note-input"
                                    min="0"
                                    max="{{ $evaluation->bareme }}"
                                    step="0.01"
                                    value="{{ old('notes.' . $inscription->id . '.valeur', $noteExistante?->valeur) }}"
                                    placeholder="Ex : 15"
                                    data-bareme="{{ $evaluation->bareme }}"
                                    data-target="appreciation-{{ $inscription->id }}"
                                >

                                    
                                </td>

                                <td>
                                    <span
                                        id="appreciation-{{ $inscription->id }}"
                                        class="badge js-appreciation-badge"
                                    >
                                        {{ $appreciation ?? 'Non saisie' }}
                                    </span>
                                </td>
                            </tr>
                        {{-- Message affiche quand la liste est vide. --}}
                        @empty
                            <tr>
                                <td colspan="4">
                                    Aucun élève inscrit pour cette classe et cette année scolaire.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="note-actions">
                    <button type="submit" class="btn btn-primary">
                        Enregistrer les notes
                    </button>

                    <a href="{{ route('evaluations.show', $evaluation) }}" class="btn">
                        Annuler
                    </a>
                    <small>
                       La note doit être comprise entre 0 et {{ number_format($evaluation->bareme, 2, ',', ' ') }}.
                    </small>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>