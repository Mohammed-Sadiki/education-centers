@props(['role'])

@php
    $label = match ($role) {
        'super_admin' => 'Super Admin',
        'center_admin' => 'Admin Centre',
        'teacher' => 'Professeur',
        'student' => 'Élève',
        default => (string) $role,
    };
    $tone = match ($role) {
        'super_admin' => 'danger',
        'center_admin' => 'primary',
        'teacher' => 'success',
        'student' => 'info',
        default => 'neutral',
    };
@endphp

<x-ui.badge :tone="$tone">{{ $label }}</x-ui.badge>
