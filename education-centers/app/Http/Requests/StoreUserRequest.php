<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        $authUser   = $this->user();
        $isSuperAdmin = $authUser->isSuperAdmin();

        // Rôles autorisés selon le rôle du créateur
        $allowedRoles = $isSuperAdmin
            ? [User::ROLE_SUPER_ADMIN, User::ROLE_CENTER_ADMIN, User::ROLE_TEACHER, User::ROLE_STUDENT]
            : [User::ROLE_TEACHER, User::ROLE_STUDENT];

        $rules = [
            'name'     => ['required', 'string', 'max:150'],
            'email'    => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', 'string', Rule::in($allowedRoles)],
            'phone'    => ['nullable', 'string', 'max:30'],
            'status'   => ['required', 'in:active,inactive'],
        ];

        // Center_id : seul le Super Admin peut le choisir librement
        if ($isSuperAdmin) {
            $rules['center_id'] = [
                'nullable',
                'integer',
                Rule::exists('centers', 'id'),
            ];
        }
        // Pour Admin Centre : center_id est forcé côté serveur dans le contrôleur (pas dans le formulaire)

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Le nom est obligatoire.',
            'email.required'     => 'L\'adresse e-mail est obligatoire.',
            'email.unique'       => 'Cette adresse e-mail est déjà utilisée.',
            'password.required'  => 'Le mot de passe est obligatoire.',
            'password.min'       => 'Le mot de passe doit comporter au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.required'      => 'Le rôle est obligatoire.',
            'role.in'            => 'Ce rôle n\'est pas autorisé.',
            'status.required'    => 'Le statut est obligatoire.',
            'status.in'          => 'Le statut doit être "active" ou "inactive".',
            'center_id.exists'   => 'Le centre sélectionné n\'existe pas.',
        ];
    }
}
