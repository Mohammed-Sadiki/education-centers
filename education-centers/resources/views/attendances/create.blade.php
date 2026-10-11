@extends('layouts.app')
@section('title', 'Saisie des présences')

@section('content')
<x-ui.page-header title="Saisie des présences" description="Choisissez une séance, puis marquez Présent / Absent / Retard.">
    <a href="{{ route('attendances.index') }}" class="ec-btn-secondary">Retour</a>
</x-ui.page-header>

@php
    $studentsUrl = route('attendances.students-for-schedule');
@endphp

<div class="ec-card p-5" x-data="attendanceForm({{ \Illuminate\Support\Js::from($studentsUrl) }}, {{ (int) ($schedule?->id ?? 0) }})">
    @if(auth()->user()->isSuperAdmin())
        <form method="GET" class="mb-4 max-w-sm">
            <label class="ec-label">Centre</label>
            <select name="center_id" class="ec-select" onchange="this.form.submit()">
                <option value="">Sélectionner</option>
                @foreach($centers as $c)
                    <option value="{{ $c->id }}" @selected($centerId == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </form>
    @endif

    <form method="POST" action="{{ route('attendances.store-session') }}">
        @csrf
        @if(auth()->user()->isSuperAdmin() && $centerId)
            <input type="hidden" name="center_id" value="{{ $centerId }}">
        @endif
        <div class="mb-4">
            <label class="ec-label" for="schedule_id">Séance <span class="text-red-600">*</span></label>
            <select name="schedule_id" id="schedule_id" class="ec-select" required x-model="scheduleId" x-on:change="loadStudents()">
                <option value="">Choisir une séance</option>
                @foreach($schedules as $s)
                    <option value="{{ $s->id }}" @selected(($schedule?->id) == $s->id)>
                        {{ optional($s->date)->format('d/m/Y') }} · {{ substr($s->start_time,0,5) }} — {{ substr($s->end_time,0,5) }} · {{ $s->classRoom?->name ?? $s->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div x-show="loading" class="py-8 text-sm text-slate-500">Chargement des étudiants…</div>

        <div x-show="!loading && students.length" class="overflow-x-auto">
            <table class="ec-table">
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(student, index) in students" :key="student.id">
                        <tr>
                            <td>
                                <input type="hidden" :name="'attendances['+index+'][student_id]'" :value="student.id">
                                <span class="font-medium" x-text="student.last_name + ' ' + student.first_name"></span>
                                <span class="block font-mono text-xs text-slate-500" x-text="student.student_number"></span>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <label class="ec-btn-sm rounded-lg border px-2 py-1">
                                        <input type="radio" :name="'attendances['+index+'][status]'" value="present" x-model="status[student.id]"> Présent
                                    </label>
                                    <label class="ec-btn-sm rounded-lg border px-2 py-1">
                                        <input type="radio" :name="'attendances['+index+'][status]'" value="absent" x-model="status[student.id]"> Absent
                                    </label>
                                    <label class="ec-btn-sm rounded-lg border px-2 py-1">
                                        <input type="radio" :name="'attendances['+index+'][status]'" value="late" x-model="status[student.id]"> Retard
                                    </label>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <p x-show="!loading && scheduleId && !students.length" class="text-sm text-slate-500">Aucun étudiant accepté pour cette classe.</p>
        <button type="submit" class="ec-btn-primary mt-6" x-bind:disabled="!students.length">Enregistrer</button>
    </form>
</div>
@endsection
