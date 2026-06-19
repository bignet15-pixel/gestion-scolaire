<x-app-layout>
    <div class="container">
        <div class="card">
            <h1>Ajouter une sanction</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('sanctions.store') }}" method="POST">
                @csrf
                @include('sanctions.partials.form', ['sanction' => null])
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('sanctions.index') }}" class="btn">Retour</a>
            </form>
        </div>
    </div>
</x-app-layout>
