<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Room $room */
        $room = $this->route('room');

        return $room && $this->user()->can('update', $room);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Room $room */
        $room = $this->route('room');
        $centerId = $room->center_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'code')
                    ->where(function ($query) use ($centerId) {
                        return $query->where('center_id', $centerId);
                    })
                    ->ignore($room->id),
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
