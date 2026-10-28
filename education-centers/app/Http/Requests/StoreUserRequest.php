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
        $authUser = $this->user();
        $isSuperAdmin = $authUser->isSuperAdmin();

        // Rôles autorisés : création uniquement de compte Professeur via ce formulaire
        // Seul le rôle Professeur est autorisé (imposé automatiquement ou vérifié serveur)
        $allowedRoles = [User::ROLE_TEACHER];

        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive'],
            'domain' => ['required', 'string', Rule::exists('domains', 'slug')],
            'formation_id' => [
                'required',
                'integer',
                Rule::exists('formations', 'id')->where(function ($query) use ($isSuperAdmin, $authUser) {
                    $centerId = $isSuperAdmin ? $this->input('center_id') : $authUser->center_id;
                    $domain = $this->input('domain');
                    if ($centerId) {
                        $query->where('center_id', $centerId);
                    }
                    if ($domain) {
                        $query->where('domain', $domain);
                    }
                }),
            ],
            // Compatibilité optionnelle si tableau domains fourni
            'domains' => ['nullable', 'array'],
            'domains.*' => ['string', Rule::exists('domains', 'slug')],
        ];

        // Center_id : obligatoire pour Super Admin créant un professeur
        if ($isSuperAdmin) {
            $rules['center_id'] = [
                'required',
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
            'name.required' => __('Le nom est obligatoire.'),
            'email.required' => __('L\'adresse e-mail est obligatoire.'),
            'email.unique' => __('Cette adresse e-mail est déjà utilisée.'),
            'password.required' => __('Le mot de passe est obligatoire.'),
            'password.min' => __('Le mot de passe doit comporter au moins 8 caractères.'),
            'password.confirmed' => __('La confirmation du mot de passe ne correspond pas.'),
            'role.required' => __('Le rôle est obligatoire.'),
            'role.in' => __('Ce rôle n\'est pas autorisé.'),
            'status.required' => __('Le statut est obligatoire.'),
            'status.in' => __('Le statut doit être "active" ou "inactive".'),
            'center_id.exists' => __('Le centre sélectionné n\'existe pas.'),
        ];
    }
}
