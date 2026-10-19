<?php

namespace App\Http\Requests;

use App\Models\Formation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Formation::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $authUser = $this->user();

        // Détermination du center_id cible pour la contrainte d'unicité
        $targetCenterId = $authUser->isCenterAdmin() ? $authUser->center_id : $this->input('center_id');

        return [
            'center_id' => [
                $authUser->isSuperAdmin() ? 'required' : 'nullable',
                'exists:centers,id',
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('formations', 'code')->where(function ($query) use ($targetCenterId) {
                    return $query->where('center_id', $targetCenterId);
                }),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_hours' => ['nullable', 'integer', 'min:0'],
            'duration_weeks' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3', Rule::in(['MAD', 'EUR', 'USD', 'GBP', 'CHF'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'center_id' => 'centre éducatif',
            'name' => 'intitulé de la formation',
            'code' => 'code de la formation',
            'description' => 'description du programme',
            'duration_hours' => 'durée en heures',
            'duration_weeks' => 'durée en semaines',
            'price' => 'tarif',
            'currency' => 'devise',
            'status' => 'statut',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'Le code de formation est déjà utilisé au sein de ce centre.',
        ];
    }
}
