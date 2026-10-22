<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Room::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $authUser = $this->user();

        // Détermination stricte du centre cible côté serveur
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
                Rule::unique('rooms', 'code')->where(function ($query) use ($targetCenterId) {
                    return $query->where('center_id', $targetCenterId);
                }),
            ],
            'capacity' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:5000'],
            'floor' => ['nullable', 'string', 'max:50'],
            'building' => ['nullable', 'string', 'max:100'],
            'equipment' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'center_id' => __('centre éducatif'),
            'name' => __('nom de la salle'),
            'code' => __('code de la salle'),
            'capacity' => __('capacité d’accueil'),
            'description' => __('description'),
            'floor' => __('étage'),
            'building' => __('bâtiment'),
            'equipment' => __('équipements et matériel'),
            'status' => __('statut'),
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'code.unique' => __('Le code de salle est déjà utilisé au sein de ce centre.'),
            'capacity.min' => __('La capacité de la salle doit être un entier strictement supérieur à 0.'),
        ];
    }
}
