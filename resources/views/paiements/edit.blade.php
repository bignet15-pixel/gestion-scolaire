<x-app-layout>
{{-- Vue Blade : resources/views/paiements/edit.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Modifier un paiement</h1>

            <p>
                <strong>Numéro :</strong> {{ $paiement->numero_paiement }}
            </p>

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

            <form action="{{ route('paiements.update', $paiement) }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf
                {{-- Methode HTTP du formulaire. --}}
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Inscription</label>
                    <select name="inscription_id" class="form-control">
                        {{-- Affiche les inscriptions dans le tableau. --}}
                        @foreach ($inscriptions as $inscription)
                            <option value="{{ $inscription->id }}" @selected(old('inscription_id', $paiement->inscription_id) == $inscription->id)>
                                {{ $inscription->eleve->matricule }}
                                —
                                {{ $inscription->eleve->nom }} {{ $inscription->eleve->prenom }}
                                —
                                {{ $inscription->classe->nom }}
                                —
                                {{ $inscription->anneeScolaire->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Montant payé</label>
                    <input type="number" name="montant" class="form-control" min="1" value="{{ old('montant', $paiement->montant) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Date paiement</label>
                    <input type="date" name="date_paiement" class="form-control" value="{{ old('date_paiement', $paiement->date_paiement?->format('Y-m-d')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Mode de paiement</label>
                    <select name="mode_paiement" class="form-control">
                        <option value="especes" @selected(old('mode_paiement', $paiement->mode_paiement) === 'especes')>Espèces</option>
                        <option value="mobile_money" @selected(old('mode_paiement', $paiement->mode_paiement) === 'mobile_money')>Mobile money</option>
                        <option value="virement" @selected(old('mode_paiement', $paiement->mode_paiement) === 'virement')>Virement</option>
                        <option value="autre" @selected(old('mode_paiement', $paiement->mode_paiement) === 'autre')>Autre</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Modifier
                </button>

                <a href="{{ route('paiements.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>