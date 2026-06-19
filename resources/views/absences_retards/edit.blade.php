<x-app-layout>
    <div class="container">
        <div class="card">
            <h1>Modifier l’événement d’assiduité</h1>

            <div class="profile-grid">
                <div><span>Élève</span><strong>{{ $evenement->inscription?->eleve?->nom }} {{ $evenement->inscription?->eleve?->prenom }}</strong></div>
                <div><span>Classe</span><strong>{{ $evenement->inscription?->classe?->nom }}</strong></div>
                <div><span>Année</span><strong>{{ $evenement->inscription?->anneeScolaire?->libelle }}</strong></div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('absences-retards.update', $evenement) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('absences_retards.partials.form', ['evenement' => $evenement])

                <button type="submit" class="btn btn-primary">Modifier</button>
                <a href="{{ route('absences-retards.show', $evenement) }}" class="btn">Retour</a>
            </form>
        </div>
    </div>
</x-app-layout>
