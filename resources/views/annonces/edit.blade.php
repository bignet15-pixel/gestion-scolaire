<x-app-layout>
    <div class="container">
        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Communication</div>
                <h1>Modifier l’annonce</h1>
                <p>
                    Si une annonce est déjà publiée, la modification ne renvoie pas automatiquement un nouveau mail.
                </p>
            </div>

            <div class="detail-actions">
                <a href="{{ route('annonces.show', $annonce) }}" class="btn">Retour</a>
            </div>
        </div>

        <div class="card">
            <form method="POST" action="{{ route('annonces.update', $annonce) }}">
                @method('PUT')
                @include('annonces._form')
            </form>
        </div>
    </div>
</x-app-layout>
