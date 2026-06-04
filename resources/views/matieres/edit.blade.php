<x-app-layout>
{{-- Vue Blade : resources/views/matieres/edit.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Modifier une matière</h1>

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

            <form action="{{ route('matieres.update', $matiere) }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf
                {{-- Methode HTTP du formulaire. --}}
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Nom de la matière</label>
                    <input type="text" name="nom" class="form-control" value="{{ old('nom', $matiere->nom) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Coefficient par défaut</label>
                    <input type="number" name="coefficient_default" class="form-control" min="1" max="10" value="{{ old('coefficient_default', $matiere->coefficient_default) }}">
                </div>

                <button type="submit" class="btn btn-primary">
                    Modifier
                </button>

                <a href="{{ route('matieres.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>