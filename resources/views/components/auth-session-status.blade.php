{{-- Vue Blade : resources/views/components/auth-session-status.blade.php --}}
{{-- Parametres du composant. --}}
@props(['status'])

{{-- Condition : $status. --}}
@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600']) }}>
        {{ $status }}
    </div>
@endif
