<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCenterRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation pour la mise à jour.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Récupère le center en cours de modification via le paramètre de route
        $centerId = $this->route('center')?->id;

        return [
            'name'        => ['required', 'string', 'max:150'],
            'code'        => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9_\-]+$/',
                Rule::unique('centers', 'code')->ignore($centerId), // Ignore le centre actuel
            ],
            'address'     => ['nullable', 'string', 'max:255'],
            'city'        => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country'     => ['nullable', 'string', 'max:100'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'email'       => ['nullable', 'email', 'max:150'],
            'status'      => ['required', 'in:active,inactive'],
            'logo'        => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'name.required'   => 'Le nom du centre est obligatoire.',
            'code.required'   => 'Le code du centre est obligatoire.',
            'code.unique'     => 'Ce code est déjà utilisé par un autre centre.',
            'code.regex'      => 'Le code ne peut contenir que des lettres majuscules, chiffres, tirets et underscores.',
            'email.email'     => 'Veuillez saisir une adresse email valide.',
            'status.required' => 'Le statut est obligatoire.',
            'status.in'       => 'Le statut doit être "active" ou "inactive".',
            'logo.image'      => 'Le logo doit être une image.',
            'logo.mimes'      => 'Le logo doit être au format JPG, JPEG, PNG ou WebP.',
            'logo.max'        => 'Le logo ne doit pas dépasser 2 Mo.',
        ];
    }

    /**
     * Prépare les données avant validation.
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
