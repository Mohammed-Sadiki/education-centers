@props(['status', 'type' => 'generic'])

@php
    $map = match ($type) {
        'enrollment' => [
            'pending' => [__('En attente'), 'warning'],
            'under_review' => [__('En examen'), 'info'],
            'accepted' => [__('Acceptée'), 'success'],
            'rejected' => [__('Refusée'), 'danger'],
            'cancelled' => [__('Annulée'), 'neutral'],
        ],
        'attendance' => [
            'present' => [__('Présent'), 'success'],
            'absent' => [__('Absent'), 'danger'],
            'late' => [__('Retard'), 'warning'],
        ],
        'payment' => [
            'pending' => [__('En attente'), 'warning'],
            'paid' => [__('Payé'), 'success'],
            'cancelled' => [__('Annulé'), 'neutral'],
            'refunded' => [__('Remboursé'), 'info'],
        ],
        'invoice' => [
            'draft' => [__('Brouillon'), 'neutral'],
            'sent' => [__('Envoyée'), 'info'],
            'paid' => [__('Payée'), 'success'],
            'cancelled' => [__('Annulée'), 'neutral'],
            'overdue' => [__('En retard'), 'danger'],
        ],
        'schedule' => [
            'scheduled' => [__('Planifié'), 'primary'],
            'completed' => [__('Terminé'), 'success'],
            'cancelled' => [__('Annulé'), 'danger'],
        ],
        'schedule_type' => [
            'course' => [__('Cours'), 'primary'],
            'exam' => [__('Examen'), 'danger'],
            'workshop' => [__('Workshop'), 'info'],
            'other' => [__('Autre'), 'neutral'],
        ],
        'announcement' => [
            'draft' => [__('Brouillon'), 'neutral'],
            'published' => [__('Publiée'), 'success'],
            'archived' => [__('Archivée'), 'warning'],
        ],
        'evaluation' => [
            'draft' => [__('Brouillon'), 'neutral'],
            'published' => [__('Publiée'), 'success'],
            'closed' => [__('Clôturée'), 'info'],
        ],
        'certificate' => [
            'draft' => [__('Brouillon'), 'neutral'],
            'issued' => [__('Émis'), 'success'],
            'revoked' => [__('Révoqué'), 'danger'],
        ],
        default => [
            'active' => [__('Actif'), 'success'],
            'inactive' => [__('Inactif'), 'neutral'],
            'issued' => [__('Émis'), 'success'],
        ],
    };
    [$label, $tone] = $map[$status] ?? [ucfirst((string) $status), 'neutral'];
@endphp

<x-ui.badge :tone="$tone">{{ $label }}</x-ui.badge>
