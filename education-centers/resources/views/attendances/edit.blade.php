@extends('layouts.app')
@section('title', __('Modifier une présence'))

@section('content')
<x-ui.page-header :title="__('Modifier une présence')" :description="$attendance->student?->full_name" />
<form method="POST" action="{{ route('attendances.update', $attendance) }}" class="ec-card max-w-xl space-y-4 p-5">
    @csrf
    @method('PATCH')
    <div>
        <label class="ec-label">{{ __('Statut') }}</label>
        <select name="status" class="ec-select" required>
            <option value="present" @selected($attendance->status === 'present')>{{ __('Présent') }}</option>
            <option value="absent" @selected($attendance->status === 'absent')>{{ __('Absent') }}</option>
            <option value="late" @selected($attendance->status === 'late')>{{ __('Retard') }}</option>
        </select>
    </div>
    <div>
        <label class="ec-label">{{ __('Heure d’arrivée') }}</label>
        <input type="time" name="arrival_time" class="ec-input" value="{{ old('arrival_time', $attendance->arrival_time) }}">
    </div>
    <div>
        <label class="ec-label">{{ __('Minutes de retard') }}</label>
        <input type="number" name="late_minutes" class="ec-input" min="0" value="{{ old('late_minutes', $attendance->late_minutes) }}">
    </div>
    <div>
        <label class="ec-label">{{ __('Justification') }}</label>
        <input name="justification" class="ec-input" value="{{ old('justification', $attendance->justification) }}">
    </div>
    <div>
        <label class="ec-label">{{ __('Statut justification') }}</label>
        <select name="justification_status" class="ec-select">
            <option value="">—</option>
            <option value="pending" @selected(old('justification_status', $attendance->justification_status) === 'pending')>{{ __('En attente') }}</option>
            <option value="approved" @selected(old('justification_status', $attendance->justification_status) === 'approved')>{{ __('Approuvée') }}</option>
            <option value="rejected" @selected(old('justification_status', $attendance->justification_status) === 'rejected')>{{ __('Refusée') }}</option>
        </select>
    </div>
    <div>
        <label class="ec-label">{{ __('Commentaire') }}</label>
        <textarea name="comment" class="ec-textarea" rows="3">{{ old('comment', $attendance->comment) }}</textarea>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('attendances.show', $attendance) }}" class="ec-btn-secondary">{{ __('Annuler') }}</a>
        <button class="ec-btn-primary">{{ __('Enregistrer') }}</button>
    </div>
</form>
@endsection
