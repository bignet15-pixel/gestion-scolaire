<x-app-layout>
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Communication</div>
                <h1>Nouvelle annonce</h1>
                <p>Le contenu sera envoyé en détail par email lors de la publication.</p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('annonces.index') }}" class="btn">Retour</a>
            </div>
        </div>

        <div class="card">
            <form method="POST" action="{{ route('annonces.store') }}">
                @include('annonces._form')
            </form>
        </div>
    </div>
</x-app-layout>
