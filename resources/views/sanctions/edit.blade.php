<x-app-layout>
    <div class="container">
        <div class="card">
            <h1>Modifier une sanction</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('sanctions.update', $sanction) }}" method="POST">
                @csrf
                @method('PUT')
                @include('sanctions.partials.form', ['sanction' => $sanction])
                <button type="submit" class="btn btn-primary">Modifier</button>
                <a href="{{ route('sanctions.show', $sanction) }}" class="btn">Retour</a>
            </form>
        </div>
    </div>
</x-app-layout>
