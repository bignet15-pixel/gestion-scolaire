{{-- Vue Blade : resources/views/components/input-label.blade.php --}}
{{-- Parametres du composant. --}}
@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700']) }}>
    {{ $value ?? $slot }}
</label>
