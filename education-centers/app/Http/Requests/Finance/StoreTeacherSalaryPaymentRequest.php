<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherSalaryPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'teacher_id'      => ['required', 'integer', 'exists:teachers,id'],
            'month'           => ['required', 'integer', 'between:1,12'],
            'year'            => ['required', 'integer', 'min:2020', 'max:2050'],
            'school_year'     => ['required', 'string', 'max:20'],
            'expected_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount'     => ['required', 'numeric', 'min:0'],
            'currency'        => ['required', 'string', 'max:10'],
            'payment_date'    => ['nullable', 'date'],
            'payment_method'  => ['nullable', 'string', 'max:50'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ];
    }
}
