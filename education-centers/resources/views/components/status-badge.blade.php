@props(['status', 'type' => 'generic'])

@php
    $map = match ($type) {
        'enrollment' => [
            'pending' => ['En attente', 'warning'],
            'under_review' => ['En examen', 'info'],
            'accepted' => ['Acceptée', 'success'],
            'rejected' => ['Refusée', 'danger'],
            'cancelled' => ['Annulée', 'neutral'],
        ],
        'attendance' => [
            'present' => ['Présent', 'success'],
            'absent' => ['Absent', 'danger'],
            'late' => ['Retard', 'warning'],
        ],
        'payment' => [
            'pending' => ['En attente', 'warning'],
            'paid' => ['Payé', 'success'],
            'cancelled' => ['Annulé', 'neutral'],
            'refunded' => ['Remboursé', 'info'],
        ],
        'invoice' => [
            'draft' => ['Brouillon', 'neutral'],
            'sent' => ['Envoyée', 'info'],
            'paid' => ['Payée', 'success'],
            'cancelled' => ['Annulée', 'neutral'],
            'overdue' => ['En retard', 'danger'],
        ],
        'schedule' => [
            'scheduled' => ['Planifié', 'primary'],
            'completed' => ['Terminé', 'success'],
            'cancelled' => ['Annulé', 'danger'],
        ],
        'schedule_type' => [
            'course' => ['Cours', 'primary'],
            'exam' => ['Examen', 'danger'],
            'workshop' => ['Workshop', 'info'],
            'other' => ['Autre', 'neutral'],
        ],
        'announcement' => [
            'draft' => ['Brouillon', 'neutral'],
            'published' => ['Publiée', 'success'],
            'archived' => ['Archivée', 'warning'],
        ],
        'evaluation' => [
            'draft' => ['Brouillon', 'neutral'],
            'published' => ['Publiée', 'success'],
            'closed' => ['Clôturée', 'info'],
        ],
        default => [
            'active' => ['Actif', 'success'],
            'inactive' => ['Inactif', 'neutral'],
            'issued' => ['Émis', 'success'],
        ],
    };
    [$label, $tone] = $map[$status] ?? [ucfirst((string) $status), 'neutral'];
@endphp

<x-ui.badge :tone="$tone">{{ $label }}</x-ui.badge>
