<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Student::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $authUser = $this->user();

        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                'unique:students,user_id',
                function ($attribute, $value, $fail) use ($authUser) {
                    $targetUser = User::find($value);
                    if (! $targetUser) {
                        return;
                    }

                    // Must have role = 'student'
                    if (! $targetUser->isStudent()) {
                        $fail("L'utilisateur sélectionné n'a pas le rôle élève (student).");
                        return;
                    }

                    // For center admin: target user must belong to their center
                    if ($authUser->isCenterAdmin() && $targetUser->center_id !== $authUser->center_id) {
                        $fail("L'utilisateur sélectionné n'appartient pas à votre centre.");
                        return;
                    }

                    // For super admin: if center_id is supplied, ensure user belongs to selected center
                    if ($authUser->isSuperAdmin() && $this->filled('center_id')) {
                        if ($targetUser->center_id != $this->input('center_id')) {
                            $fail("L'utilisateur sélectionné n'appartient pas au centre choisi.");
                            return;
                        }
                    }
                },
            ],
            'center_id' => [
                $authUser->isSuperAdmin() ? 'required' : 'nullable',
                'exists:centers,id',
            ],
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
            'user_id' => 'compte utilisateur élève',
            'center_id' => 'centre éducatif',
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'date_of_birth' => 'date de naissance',
            'place_of_birth' => 'lieu de naissance',
            'gender' => 'genre',
            'nationality' => 'nationalité',
            'address' => 'adresse',
            'postal_code' => 'code postal',
            'city' => 'ville',
            'country' => 'pays',
            'phone' => 'téléphone',
            'emergency_contact_name' => 'nom du contact d’urgence',
            'emergency_contact_phone' => 'téléphone du contact d’urgence',
            'emergency_contact_relation' => 'lien avec le contact d’urgence',
            'notes' => 'remarques',
            'status' => 'statut',
        ];
    }
}
