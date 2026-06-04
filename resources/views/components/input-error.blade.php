{{-- Vue Blade : resources/views/components/input-error.blade.php --}}
{{-- Parametres du composant. --}}
@props(['messages'])

{{-- Condition : $messages. --}}
@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1']) }}>
        {{-- Affiche les elements de (array) $messages. --}}
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
