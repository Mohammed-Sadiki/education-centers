<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $authUser = $this->user();
        $targetUser = $this->route('user');
        $isSuperAdmin = $authUser->isSuperAdmin();

        // Rôles autorisés selon le rôle de l'éditeur
        $allowedRoles = $isSuperAdmin
            ? [User::ROLE_SUPER_ADMIN, User::ROLE_CENTER_ADMIN, User::ROLE_TEACHER, User::ROLE_STUDENT]
            : [User::ROLE_TEACHER, User::ROLE_STUDENT];

        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($targetUser?->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive'],
        ];

        // Center_id : seul le Super Admin peut le modifier
        if ($isSuperAdmin) {
            $rules['center_id'] = [
                'nullable',
                'integer',
                Rule::exists('centers', 'id'),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Le nom est obligatoire.'),
            'email.required' => __('L\'adresse e-mail est obligatoire.'),
            'email.unique' => __('Cette adresse e-mail est déjà utilisée.'),
            'password.min' => __('Le nouveau mot de passe doit comporter au moins 8 caractères.'),
            'password.confirmed' => __('La confirmation du mot de passe ne correspond pas.'),
            'role.required' => __('Le rôle est obligatoire.'),
            'role.in' => __('Ce rôle n\'est pas autorisé.'),
            'status.required' => __('Le statut est obligatoire.'),
            'status.in' => __('Le statut doit être "active" ou "inactive".'),
            'center_id.exists' => __('Le centre sélectionné n\'existe pas.'),
        ];
    }
}
