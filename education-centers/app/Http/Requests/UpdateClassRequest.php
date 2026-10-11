<?php

namespace App\Http\Requests;

use App\Models\ClassRoom;
use App\Models\Formation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var ClassRoom $class */
        $class = $this->route('class');

        return $class && $this->user()->can('update', $class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var ClassRoom $class */
        $class = $this->route('class');
        $centerId = $class->center_id;

        return [
            'formation_id' => [
                'required',
                'integer',
                'exists:formations,id',
                function ($attribute, $value, $fail) use ($centerId) {
                    $formation = Formation::find($value);
                    if (! $formation) {
                        return;
                    }

                    if ($formation->center_id != $centerId) {
                        $fail("La formation sélectionnée n'appartient pas au même centre que la classe.");
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('classes', 'code')
                    ->where(function ($query) use ($centerId) {
                        return $query->where('center_id', $centerId);
                    })
                    ->ignore($class->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'formation_id' => 'formation associée',
            'name' => 'intitulé de la classe',
            'code' => 'code de la classe',
            'description' => 'description',
            'capacity' => 'capacité d’accueil',
            'start_date' => 'date de début',
            'end_date' => 'date de fin',
            'status' => 'statut',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'Le code de classe est déjà utilisé au sein de ce centre.',
            'capacity.min' => 'La capacité de la classe doit être strictement supérieure à 0.',
            'end_date.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }
}
