@extends('layouts.app')
@section('title', 'Modifier une présence')

@section('content')
<x-ui.page-header title="Modifier une présence" :description="$attendance->student?->full_name" />
<form method="POST" action="{{ route('attendances.update', $attendance) }}" class="ec-card max-w-xl space-y-4 p-5">
    @csrf
    @method('PATCH')
    <div>
        <label class="ec-label">Statut</label>
        <select name="status" class="ec-select" required>
            <option value="present" @selected($attendance->status==='present')>Présent</option>
            <option value="absent" @selected($attendance->status==='absent')>Absent</option>
            <option value="late" @selected($attendance->status==='late')>Retard</option>
        </select>
    </div>
    <div>
        <label class="ec-label">Heure d’arrivée</label>
        <input type="time" name="arrival_time" class="ec-input" value="{{ old('arrival_time', $attendance->arrival_time) }}">
    </div>
    <div>
        <label class="ec-label">Minutes de retard</label>
        <input type="number" name="late_minutes" class="ec-input" min="0" value="{{ old('late_minutes', $attendance->late_minutes) }}">
    </div>
    <div>
        <label class="ec-label">Justification</label>
        <input name="justification" class="ec-input" value="{{ old('justification', $attendance->justification) }}">
    </div>
    <div>
        <label class="ec-label">Statut justification</label>
        <select name="justification_status" class="ec-select">
            <option value="">—</option>
            <option value="pending" @selected(old('justification_status', $attendance->justification_status)==='pending')>En attente</option>
            <option value="approved" @selected(old('justification_status', $attendance->justification_status)==='approved')>Approuvée</option>
            <option value="rejected" @selected(old('justification_status', $attendance->justification_status)==='rejected')>Refusée</option>
        </select>
    </div>
    <div>
        <label class="ec-label">Commentaire</label>
        <textarea name="comment" class="ec-textarea" rows="3">{{ old('comment', $attendance->comment) }}</textarea>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('attendances.show', $attendance) }}" class="ec-btn-secondary">Annuler</a>
        <button class="ec-btn-primary">Enregistrer</button>
    </div>
</form>
@endsection
