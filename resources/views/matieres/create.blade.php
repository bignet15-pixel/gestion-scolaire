<x-app-layout>
{{-- Vue Blade : resources/views/matieres/create.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Ajouter une matière</h1>

            {{-- Condition : $errors->any(). --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        {{-- Affiche les messages d erreur de validation. --}}
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('matieres.store') }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf

                <div class="form-group">
                    <label class="form-label">Nom de la matière</label>
                    <input type="text" name="nom" class="form-control" placeholder="Ex: Mathématiques" value="{{ old('nom') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Coefficient par défaut</label>
                    <input type="number" name="coefficient_default" class="form-control" min="1" max="10" value="{{ old('coefficient_default', 1) }}">
                </div>

                <button type="submit" class="btn btn-primary">
                    Enregistrer
                </button>

                <a href="{{ route('matieres.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>