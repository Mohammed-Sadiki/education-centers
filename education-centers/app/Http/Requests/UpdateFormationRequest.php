<?php

namespace App\Http\Requests;

use App\Models\Formation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Formation $formation */
        $formation = $this->route('formation');

        return $formation && $this->user()->can('update', $formation);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Formation $formation */
        $formation = $this->route('formation');
        $authUser = $this->user();

        // Le center_id est immuable pour l'admin centre, ou issu de la formation existante
        $centerId = $formation->center_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('formations', 'code')
                    ->where(function ($query) use ($centerId) {
                        return $query->where('center_id', $centerId);
                    })
                    ->ignore($formation->id),
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
            'name' => __('intitulé de la formation'),
            'code' => __('code de la formation'),
            'description' => __('description du programme'),
            'duration_hours' => __('durée en heures'),
            'duration_weeks' => __('durée en semaines'),
            'price' => __('tarif'),
            'currency' => __('devise'),
            'status' => __('statut'),
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'code.unique' => __('Le code de formation est déjà utilisé au sein de ce centre.'),
        ];
    }
}
