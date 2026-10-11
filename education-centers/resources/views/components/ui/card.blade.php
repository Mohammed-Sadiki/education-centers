@props(['padding' => true])

<div {{ $attributes->merge(['class' => 'ec-card'.($padding ? ' p-5' : '')]) }}>
    {{ $slot }}
</div>
