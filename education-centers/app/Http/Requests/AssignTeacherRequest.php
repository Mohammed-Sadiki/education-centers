<?php

namespace App\Http\Requests;

use App\Models\ClassRoom;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssignTeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var ClassRoom $class */
        $class = $this->route('class');

        return $class && $this->user()->can('assignTeacher', $class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var ClassRoom $class */
        $class = $this->route('class');

        return [
            'teacher_id' => [
                'required',
                'integer',
                'exists:teachers,id',
                function ($attribute, $value, $fail) use ($class) {
                    $teacher = Teacher::find($value);
                    if (! $teacher) {
                        return;
                    }

                    // Le professeur doit être actif
                    if (! $teacher->isActive()) {
                        $fail(__('Le professeur sélectionné n\'est pas actif.'));

                        return;
                    }

                    // Le professeur et la classe doivent appartenir au même centre
                    if ($teacher->center_id !== $class->center_id) {
                        $fail(__('Le professeur sélectionné n\'appartient pas au même centre que la classe.'));

                        return;
                    }

                    // Le professeur ne doit pas déjà être affecté à cette classe
                    if ($class->hasTeacher($teacher)) {
                        $fail(__('Ce professeur est déjà affecté à cette classe.'));

                        return;
                    }
                },
            ],
        ];
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'teacher_id' => __('professeur'),
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'teacher_id.required' => __('Veuillez sélectionner un professeur.'),
            'teacher_id.exists' => __('Le professeur sélectionné est introuvable.'),
        ];
    }
}
