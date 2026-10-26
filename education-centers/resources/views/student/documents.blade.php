@extends('layouts.app')

@section('title', __('Mes documents'))

@section('content')
    <x-ui.page-header
        :title="__('Mes documents')"
        :description="__('Vos documents personnels, téléchargeables et imprimables lorsqu’ils sont au format PDF ou image.').' '.($student?->center?->name ?? auth()->user()->center?->name)"
    />

    <form method="GET" action="{{ route('student.documents') }}" class="ec-card mb-5 p-4 sm:p-5">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
            <label class="block sm:col-span-2">
                <span class="ec-label">{{ __('Type de document') }}</span>
                <select name="type" class="ec-select">
                    <option value="">{{ __('Tous les types') }}</option>
                    @foreach(['identity' => __('Identité'), 'diploma' => __('Diplôme'), 'certificate' => __('Certificat'), 'attestation' => __('Attestation'), 'administrative' => __('Administratif'), 'other' => __('Autre')] as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="ec-btn-primary">
                    <x-icon name="filter" class="h-4 w-4" /> {{ __('Filtrer') }}
                </button>
                @if(request()->filled('type'))
                    <a href="{{ route('student.documents') }}" class="ec-btn-ghost">{{ __('Réinitialiser') }}</a>
                @endif
            </div>
        </div>
    </form>

    @if($documents->isEmpty())
        <x-ui.card>
            <x-ui.empty
                :title="__('Aucun document')"
                :description="__('Aucun document actif n’est disponible dans votre espace pour le moment.')"
            />
        </x-ui.card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($documents as $document)
                <article class="ec-panel flex flex-col">
                    <div class="flex items-start gap-3 p-5">
                        <span class="ec-icon-chip shrink-0 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                            <x-icon :name="$document->isPrintable() ? 'document' : 'clipboard'" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate font-semibold text-slate-800 dark:text-slate-100">{{ $document->title }}</h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $document->type_label }}
                                · {{ $document->formatted_size }}
                                @if($document->created_at)
                                    · {{ format_date($document->created_at, 'd/m/Y') }}
                                @endif
                            </p>
                        </div>
                    </div>

                    @if($document->description)
                        <p class="line-clamp-2 px-5 pb-3 text-sm text-slate-500 dark:text-slate-400">{{ $document->description }}</p>
                    @endif

                    <div class="mt-auto flex flex-wrap items-center gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-800/30">
                        <a href="{{ route('student.documents.download', $document) }}" class="ec-btn-secondary ec-btn-sm">
                            <x-icon name="download" class="h-3.5 w-3.5" /> {{ __('Télécharger') }}
                        </a>
                        @if($document->isPrintable())
                            <a href="{{ route('student.documents.preview', $document) }}" target="_blank" rel="noopener" class="ec-btn-ghost ec-btn-sm">
                                <x-icon name="printer" class="h-3.5 w-3.5" /> {{ __('Imprimer') }}
                            </a>
                        @else
                            <span class="text-xs text-slate-400">{{ __('Aperçu non disponible') }}</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        @if($documents->hasPages())
            <div class="mt-4">{{ $documents->links() }}</div>
        @endif
    @endif
@endsection
