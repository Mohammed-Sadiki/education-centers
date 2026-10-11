@extends('layouts.app')
@section('title', 'Nouveau document')

@section('content')
<x-ui.page-header title="Ajouter un document" />
<form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="ec-card max-w-xl space-y-4 p-5">
    @csrf
    @if(auth()->user()->isSuperAdmin())
        <select name="center_id" class="ec-select" required>
            @foreach($centers as $c)
                <option value="{{ $c->id }}" @selected($centerId==$c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    @endif
    <div>
        <label class="ec-label">Étudiant</label>
        <select name="student_id" class="ec-select" required>
            @foreach($students as $student)
                <option value="{{ $student->id }}" @selected($preselectedStudentId==$student->id)>{{ $student->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="ec-label">Type</label>
        <select name="type" class="ec-select" required>
            @foreach(['identity'=>'Identité','diploma'=>'Diplôme','certificate'=>'Certificat','attestation'=>'Attestation','administrative'=>'Administratif','other'=>'Autre'] as $k=>$v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="ec-label">Titre</label>
        <input name="title" class="ec-input" required>
    </div>
    <div>
        <label class="ec-label">Description</label>
        <textarea name="description" class="ec-textarea" rows="3"></textarea>
    </div>
    <div>
        <label class="ec-label">Fichier</label>
        <input type="file" name="file" class="ec-input" required>
        <p class="ec-help">PDF, images ou Word — 10 Mo max.</p>
    </div>
    <button class="ec-btn-primary">Uploader</button>
</form>
@endsection
