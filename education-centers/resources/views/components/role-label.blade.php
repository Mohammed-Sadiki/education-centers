@props(['role'])

@php
    $label = match ($role) {
        'super_admin' => __('Super Admin'),
        'center_admin' => __('Admin Centre'),
        'teacher' => __('Professeur'),
        'student' => __('Élève'),
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
