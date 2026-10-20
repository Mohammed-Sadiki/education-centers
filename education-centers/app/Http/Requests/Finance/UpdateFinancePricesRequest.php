<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinancePricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'formations'                    => ['nullable', 'array'],
            'formations.*.id'               => ['required', 'integer', 'exists:formations,id'],
            'formations.*.annual_price'     => ['required', 'numeric', 'min:0'],
            'formations.*.domain'           => ['nullable', 'string', 'max:50'],
            'teachers'                      => ['nullable', 'array'],
            'teachers.*.id'                 => ['required', 'integer', 'exists:teachers,id'],
            'teachers.*.monthly_salary'     => ['required', 'numeric', 'min:0'],
            'teachers.*.domain'             => ['nullable', 'string', 'max:50'],
        ];
    }
}
