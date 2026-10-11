@extends('layouts.app')

@section('title', 'Centres — Education Centers')
@section('description', 'Liste de tous les centres de formation')

@section('breadcrumb')
    <li class="breadcrumb-item active">Centres</li>
@endsection

@section('content')
<div class="container-fluid px-4">

    {{-- En-tête --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold">
                <i class="bi bi-buildings me-2 text-primary"></i>Centres de formation
            </h1>
            <p class="text-muted mb-0 small">Gestion de tous les centres &mdash; {{ $centers->total() }} centre(s)</p>
        </div>
        <a href="{{ route('centers.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Nouveau centre</span>
        </a>
    </div>

    {{-- Tableau --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            @if($centers->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-buildings display-4 d-block mb-3 opacity-25"></i>
                    <p class="mb-1 fw-semibold">Aucun centre créé</p>
                    <p class="small">Cliquez sur « Nouveau centre » pour commencer.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light border-bottom">
                            <tr>
                                <th class="ps-4">Centre</th>
                                <th>Code</th>
                                <th>Ville</th>
                                <th>Téléphone</th>
                                <th>Email</th>
                                <th class="text-center">Statut</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($centers as $center)
                                <tr>
                                    {{-- Nom --}}
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            @if($center->logo_url)
                                                <img src="{{ $center->logo_url }}" alt="{{ $center->name }}"
                                                     class="avatar-center">
                                            @else
                                                <div class="avatar-center bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary fw-bold rounded-2">
                                                    {{ strtoupper(substr($center->name, 0, 2)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <div class="fw-semibold">{{ $center->name }}</div>
                                                <div class="text-muted small">{{ $center->country }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Code --}}
                                    <td>
                                        <code class="bg-light px-2 py-1 rounded small">{{ $center->code }}</code>
                                    </td>

                                    {{-- Ville --}}
                                    <td>
                                        <i class="bi bi-geo-alt text-muted me-1"></i>
                                        {{ $center->city ?? '—' }}
                                        @if($center->postal_code)
                                            <span class="text-muted small ms-1">{{ $center->postal_code }}</span>
                                        @endif
                                    </td>

                                    {{-- Téléphone --}}
                                    <td>
                                        @if($center->phone)
                                            <a href="tel:{{ $center->phone }}" class="text-decoration-none text-body">
                                                <i class="bi bi-telephone text-muted me-1"></i>{{ $center->phone }}
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    {{-- Email --}}
                                    <td>
                                        @if($center->email)
                                            <a href="mailto:{{ $center->email }}" class="text-decoration-none">
                                                <i class="bi bi-envelope text-muted me-1"></i>
                                                <span class="small">{{ $center->email }}</span>
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    {{-- Statut --}}
                                    <td class="text-center">
                                        @if($center->isActive())
                                            <span class="badge badge-status-active rounded-pill px-3">Actif</span>
                                        @else
                                            <span class="badge badge-status-inactive rounded-pill px-3">Inactif</span>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('centers.show', $center) }}"
                                               class="btn btn-outline-secondary" title="Voir">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('centers.edit', $center) }}"
                                               class="btn btn-outline-primary" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-outline-danger"
                                                    title="Supprimer"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal"
                                                    data-center-id="{{ $center->id }}"
                                                    data-center-name="{{ $center->name }}"
                                                    data-center-action="{{ route('centers.destroy', $center) }}">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($centers->hasPages())
                    <div class="px-4 py-3 border-top">
                        {{ $centers->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

{{-- Modal de confirmation de suppression --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmer la suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-1">Vous êtes sur le point de supprimer le centre :</p>
                <p class="fw-bold fs-5 mb-0" id="modalCenterName"></p>
                <div class="alert alert-warning mt-3 mb-0 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Si des utilisateurs sont rattachés à ce centre, la suppression sera refusée.
                    Préférez la désactivation.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form id="deleteForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3-fill me-1"></i>Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Remplissage dynamique du modal de suppression
document.getElementById('deleteModal').addEventListener('show.bs.modal', function (event) {
    const btn    = event.relatedTarget;
    const name   = btn.getAttribute('data-center-name');
    const action = btn.getAttribute('data-center-action');
    document.getElementById('modalCenterName').textContent = name;
    document.getElementById('deleteForm').setAttribute('action', action);
});
</script>
@endpush
