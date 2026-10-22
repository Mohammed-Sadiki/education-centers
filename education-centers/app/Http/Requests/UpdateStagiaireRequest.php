<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStagiaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        $stagiaire = $this->route('stagiaire');

        return $stagiaire && $this->user()->can('update', $stagiaire);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('currency')) {
            $this->merge(['currency' => default_currency()]);
        }
        if ($this->filled('birth_date') && ! $this->filled('date_of_birth')) {
            $this->merge(['date_of_birth' => $this->input('birth_date')]);
        }
        if ($this->filled('birth_place') && ! $this->filled('place_of_birth')) {
            $this->merge(['place_of_birth' => $this->input('birth_place')]);
        }
        if ($this->filled('cin_delivered_at') && ! $this->filled('cin_issued_date')) {
            $this->merge(['cin_issued_date' => $this->input('cin_delivered_at')]);
        }
        if ($this->filled('cin_delivered_place') && ! $this->filled('cin_issued_place')) {
            $this->merge(['cin_issued_place' => $this->input('cin_delivered_place')]);
        }
        if ($this->input('gender') === 'homme') {
            $this->merge(['gender' => 'male']);
        } elseif ($this->input('gender') === 'femme') {
            $this->merge(['gender' => 'female']);
        } elseif ($this->input('gender') === 'autre') {
            $this->merge(['gender' => 'other']);
        }
    }

    public function rules(): array
    {
        return [
            // Informations personnelles
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'center_id' => ['nullable', 'exists:centers,id'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other', 'homme', 'femme', 'autre'])],
            'nationality' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],

            // Identité (CIN)
            'cin_number' => ['nullable', 'string', 'max:50'],
            'cin_issued_date' => ['nullable', 'date'],
            'cin_delivered_at' => ['nullable', 'date'],
            'cin_issued_place' => ['nullable', 'string', 'max:255'],
            'cin_delivered_place' => ['nullable', 'string', 'max:255'],
            'cin_recto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'cin_verso' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],

            // Stage
            'stage_start_date' => ['nullable', 'date'],
            'stage_end_date' => ['nullable', 'date', 'after_or_equal:stage_start_date'],
            'stage_type' => ['nullable', 'string', 'max:255'],
            'stage_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', 'string', Rule::in(['nouveau', 'en_cours', 'termine', 'suspendu', 'annule'])],
            'notes' => ['nullable', 'string', 'max:2000'],

            // Diplômes nouveaux
            'diplomas' => ['nullable', 'array'],
            'diplomas.*.title' => ['nullable', 'string', 'max:255'],
            'diplomas.*.institution' => ['nullable', 'string', 'max:255'],
            'diplomas.*.year' => ['nullable', 'max:10'],
            'diplomas.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            // Autres documents nouveaux
            'other_documents' => ['nullable', 'array'],
            'other_documents.*.title' => ['nullable', 'string', 'max:255'],
            'other_documents.*.type' => ['nullable', 'string', 'max:50'],
            'other_documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => __('prénom'),
            'last_name' => __('nom'),
            'center_id' => __('centre'),
            'date_of_birth' => __('date de naissance'),
            'place_of_birth' => __('lieu de naissance'),
            'gender' => __('sexe'),
            'nationality' => __('nationalité'),
            'address' => __('adresse'),
            'phone' => __('téléphone'),
            'email' => __('adresse e-mail'),
            'cin_number' => __('numéro CIN'),
            'cin_issued_date' => __('date de délivrance CIN'),
            'cin_issued_place' => __('lieu de délivrance CIN'),
            'cin_recto' => __('CIN recto'),
            'cin_verso' => __('CIN verso'),
            'stage_start_date' => __('date de début du stage'),
            'stage_end_date' => __('date de fin du stage'),
            'stage_type' => __('type de stage'),
            'stage_price' => __('prix du stage'),
            'currency' => __('devise'),
            'status' => __('statut'),
            'notes' => __('notes'),
        ];
    }

    public function messages(): array
    {
        return [
            'stage_price.numeric' => __('Le prix du stage doit être un montant numérique valide.'),
            'stage_price.min' => __('Le prix du stage doit être supérieur ou égal à 0.'),
            'stage_end_date.after_or_equal' => __('La date de fin du stage doit être postérieure ou égale à la date de début.'),
        ];
    }
}
