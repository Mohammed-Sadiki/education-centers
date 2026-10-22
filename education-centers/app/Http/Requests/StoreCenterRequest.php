<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCenterRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     * La logique d'autorisation fine (rôles) sera implémentée à l'étape suivante.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', 'unique:centers,code', 'regex:/^[A-Z0-9_\-]+$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'status' => ['required', 'in:active,inactive'],
            'logo' => [
                'nullable',
                'file',
                'image',                    // Vérifie que c'est une image (MIME)
                'mimes:jpg,jpeg,png,webp',  // Extensions autorisées uniquement
                'max:2048',                 // Max 2 MB
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Le nom du centre est obligatoire.'),
            'code.required' => __('Le code du centre est obligatoire.'),
            'code.unique' => __('Ce code est déjà utilisé par un autre centre.'),
            'code.regex' => __('Le code ne peut contenir que des lettres majuscules, chiffres, tirets et underscores.'),
            'email.email' => __('Veuillez saisir une adresse email valide.'),
            'status.required' => __('Le statut est obligatoire.'),
            'status.in' => __('Le statut doit être "active" ou "inactive".'),
            'logo.image' => __('Le logo doit être une image.'),
            'logo.mimes' => __('Le logo doit être au format JPG, JPEG, PNG ou WebP.'),
            'logo.max' => __('Le logo ne doit pas dépasser 2 Mo.'),
        ];
    }

    /**
     * Prépare les données avant validation.
     * Force le code en majuscules.
     */
    protected function prepareForValidation(): void
    {
        if ($this->code) {
            $this->merge([
                'code' => strtoupper(trim($this->code)),
            ]);
        }
    }
}
