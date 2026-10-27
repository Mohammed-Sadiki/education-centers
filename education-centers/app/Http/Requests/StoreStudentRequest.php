<?php

namespace App\Http\Requests;

use App\Models\Formation;
use App\Models\Student;
use App\Models\User;
use App\Support\DomainRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
     * @return array<string, ValidationRule|array<mixed>|string>
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
                        $fail(__('L\'utilisateur sélectionné n\'a pas le rôle élève (student).'));

                        return;
                    }

                    // For center admin: target user must belong to their center
                    if ($authUser->isCenterAdmin() && $targetUser->center_id !== $authUser->center_id) {
                        $fail(__('L\'utilisateur sélectionné n\'appartient pas à votre centre.'));

                        return;
                    }

                    // For super admin: if center_id is supplied, ensure user belongs to selected center
                    if ($authUser->isSuperAdmin() && $this->filled('center_id')) {
                        if ($targetUser->center_id != $this->input('center_id')) {
                            $fail(__('L\'utilisateur sélectionné n\'appartient pas au centre choisi.'));

                            return;
                        }
                    }
                },
            ],
            'center_id' => [
                $authUser->isSuperAdmin() ? 'required' : 'nullable',
                'exists:centers,id',
            ],
            // Cascade centralisée (page Domaines) : domaine puis formation.
            // Facultatifs pour rester compatibles avec les appels existants,
            // mais vérifiés côté serveur dès qu'ils sont fournis (cf. withValidator).
            'domain' => ['nullable', 'string', Rule::in(DomainRegistry::slugs())],
            'formation_id' => ['nullable', 'integer', 'exists:formations,id'],
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
     * Contrôles croisés de la cascade centre → domaine → formation.
     * Jamais confiance au center_id transmis par le navigateur pour un Admin
     * Centre : le centre effectif est celui du compte connecté.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $authUser = $this->user();

            if (! $this->filled('formation_id')) {
                return;
            }

            $formation = Formation::find($this->input('formation_id'));
            if (! $formation) {
                return; // Déjà traité par la règle exists.
            }

            // Centre effectif, déterminé côté serveur.
            $targetUser = User::find($this->input('user_id'));
            $centerId = $authUser->isCenterAdmin()
                ? (int) $authUser->center_id
                : (int) ($targetUser?->center_id ?? $this->input('center_id'));

            if (! $formation->isActive()) {
                $validator->errors()->add('formation_id', __('La formation sélectionnée est inactive.'));
            }

            // Isolation multi-centres : la formation doit appartenir au centre retenu.
            if ((int) $formation->center_id !== $centerId) {
                $validator->errors()->add('formation_id', __('La formation sélectionnée n\'appartient pas au centre choisi.'));
            }

            // Cohérence domaine / formation (le domaine est celui de la formation).
            if ($this->filled('domain') && $formation->domain !== $this->input('domain')) {
                $validator->errors()->add('formation_id', __('La formation sélectionnée n\'appartient pas au domaine choisi.'));
            }
        });
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'user_id' => __('compte utilisateur élève'),
            'center_id' => __('centre éducatif'),
            'domain' => __('domaine de formation'),
            'formation_id' => __('formation'),
            'first_name' => __('prénom'),
            'last_name' => __('nom'),
            'date_of_birth' => __('date de naissance'),
            'place_of_birth' => __('lieu de naissance'),
            'gender' => __('genre'),
            'nationality' => __('nationalité'),
            'address' => __('adresse'),
            'postal_code' => __('code postal'),
            'city' => __('ville'),
            'country' => __('pays'),
            'phone' => __('téléphone'),
            'emergency_contact_name' => __('nom du contact d’urgence'),
            'emergency_contact_phone' => __('téléphone du contact d’urgence'),
            'emergency_contact_relation' => __('lien avec le contact d’urgence'),
            'notes' => __('remarques'),
            'status' => __('statut'),
        ];
    }
}
