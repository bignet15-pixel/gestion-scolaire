<x-app-layout>
    <div class="container">
        @php
            $affectation = $emploi_du_temps->affectation;
            $classe = $affectation?->classe;
            $anneeScolaire = $classe?->anneeScolaire;
            $matiere = $affectation?->matiere;
            $enseignant = $affectation?->enseignant;
            $periode = $emploi_du_temps->date_fin
                ? 'Du ' . ($emploi_du_temps->date_debut?->format('d/m/Y') ?? '-') . ' au ' . $emploi_du_temps->date_fin->format('d/m/Y')
                : 'Depuis le ' . ($emploi_du_temps->date_debut?->format('d/m/Y') ?? '-');
        @endphp

        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Détail du créneau</div>

                <h1>{{ ucfirst($emploi_du_temps->jour) }} {{ $emploi_du_temps->heure_debut->format('H:i') }} - {{ $emploi_du_temps->heure_fin->format('H:i') }}</h1>

                <p>
                    {{ $matiere?->nom ?? '-' }} pour la classe {{ $classe?->nom ?? '-' }},
                    assuré par {{ $enseignant?->name ?? '-' }}.
                </p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('emplois-du-temps.index') }}" class="btn">
                    Retour
                </a>

                @if (! $anneeScolaire?->estFermee())
                    <a href="{{ route('emplois-du-temps.edit', $emploi_du_temps) }}" class="btn btn-primary">
                        Modifier
                    </a>
                @endif
            </div>
        </div>

        <div class="detail-grid emploi-detail-grid">
            <div class="detail-info-card">
                <div class="detail-label">Année scolaire</div>
                <div class="detail-value">{{ $anneeScolaire?->libelle ?? '-' }}</div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Classe</div>
                <div class="detail-value">{{ $classe?->nom ?? '-' }}</div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Matière</div>
                <div class="detail-value">{{ $matiere?->nom ?? '-' }}</div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Enseignant</div>
                <div class="detail-value">{{ $enseignant?->name ?? '-' }}</div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Jour</div>
                <div class="detail-value">{{ ucfirst($emploi_du_temps->jour) }}</div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Horaire</div>
                <div class="detail-value">
                    {{ $emploi_du_temps->heure_debut->format('H:i') }} - {{ $emploi_du_temps->heure_fin->format('H:i') }}
                </div>
            </div>

            <div class="detail-info-card detail-info-card-wide">
                <div class="detail-label">Période</div>
                <div class="detail-value">{{ $periode }}</div>
            </div>

            <div class="detail-info-card">
                <div class="detail-label">Salle</div>
                <div class="detail-value">{{ $emploi_du_temps->salle ?? '-' }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
