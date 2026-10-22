<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Student $student */
        $student = $this->route('student');

        return $student && $this->user()->can('update', $student);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'nationality' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'first_name' => __('prénom'),
            'last_name' => __('nom'),
            'date_of_birth' => __('date de naissance'),
            'place_of_birth' => __('lieu de naissance'),
            'gender' => __('genre'),
            'nationality' => __('nationalité'),
            'address' => __('adresse'),
            'postal_code' => __('code postal'),
            'city' => __('ville'),
            'country' => __('pays'),
            'phone' => __('téléphone'),
            'emergency_contact_name' => __('nom du contact d’urgence'),
            'emergency_contact_phone' => __('téléphone du contact d’urgence'),
            'emergency_contact_relation' => __('lien avec le contact d’urgence'),
            'notes' => __('remarques'),
            'status' => __('statut'),
        ];
    }
}
