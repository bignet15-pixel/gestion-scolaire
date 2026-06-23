<x-app-layout>
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="detail-header-card">
            <div>
                <div class="detail-kicker">Notifications</div>
                <h1>Mes notifications</h1>
                <p>Consultez les informations qui nécessitent votre attention dans votre espace.</p>
            </div>

            <div class="detail-actions">
                <form method="POST" action="{{ route('notifications.tout-marquer-lu') }}">
                    @csrf
                    <button type="submit" class="btn">Tout marquer comme lu</button>
                </form>
            </div>
        </div>

        <div class="card">
            <form method="GET" action="{{ route('notifications.index') }}" class="filter-form">
                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select name="statut" id="statut" class="form-control">
                        <option value="">Toutes</option>
                        <option value="non_lues" @selected(request('statut') === 'non_lues')>Non lues</option>
                        <option value="lues" @selected(request('statut') === 'lues')>Lues</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="type">Type</label>
                    <select name="type" id="type" class="form-control">
                        <option value="">Tous</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn">Filtrer</button>
                <a href="{{ route('notifications.index') }}" class="btn">Réinitialiser</a>
            </form>
        </div>

        <div class="notification-list">
            @forelse ($notifications as $notification)
                <div class="notification-item {{ $notification->lue ? '' : 'notification-item-unread' }}">
                    <div>
                        <div class="notification-meta">
                            {{ $notification->libelleType() }} · {{ $notification->created_at?->format('d/m/Y H:i') }}
                        </div>
                        <h2>{{ $notification->titre }}</h2>
                        <p>{{ \Illuminate\Support\Str::limit($notification->message, 180) }}</p>
                    </div>

                    <div class="notification-actions">
                        <span class="badge {{ $notification->lue ? 'badge-success' : 'badge-warning' }}">
                            {{ $notification->lue ? 'Lue' : 'Non lue' }}
                        </span>
                        <a href="{{ route('notifications.show', $notification) }}" class="btn btn-primary">Voir</a>
                    </div>
                </div>
            @empty
                <div class="card">
                    Aucune notification trouvée.
                </div>
            @endforelse
        </div>

        {{ $notifications->links() }}
    </div>
</x-app-layout>
