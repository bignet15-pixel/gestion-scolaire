<x-app-layout>
{{-- Vue Blade : resources/views/emplois_du_temps/show.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Détail du créneau</h1>

            <p><strong>Année scolaire :</strong> {{ $emploi_du_temps->affectation->classe->anneeScolaire->libelle }}</p>
            <p><strong>Classe :</strong> {{ $emploi_du_temps->affectation->classe->nom }}</p>
            <p><strong>Matière :</strong> {{ $emploi_du_temps->affectation->matiere->nom }}</p>
            <p><strong>Enseignant :</strong> {{ $emploi_du_temps->affectation->enseignant->name }}</p>
            <p><strong>Jour :</strong> {{ ucfirst($emploi_du_temps->jour) }}</p>
            <p><strong>Heure :</strong> {{ $emploi_du_temps->heure_debut->format('H:i') }} - {{ $emploi_du_temps->heure_fin->format('H:i') }}</p>
            <p><strong>Salle :</strong> {{ $emploi_du_temps->salle ?? '-' }}</p>

            <p>
                <a href="{{ route('emplois-du-temps.index') }}" class="btn">
                    Retour
                </a>

                {{-- Condition : ! $emploi_du_temps->affectation?->classe?->anneeScolaire?->estFermee(). --}}
                @if (! $emploi_du_temps->affectation?->classe?->anneeScolaire?->estFermee())
                    <a href="{{ route('emplois-du-temps.edit', $emploi_du_temps) }}" class="btn btn-primary">
                        Modifier
                    </a>
                @endif
            </p>
        </div>
    </div>
</x-app-layout>
