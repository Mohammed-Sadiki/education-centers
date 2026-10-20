<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id'     => ['required', 'integer', 'exists:students,id'],
            'formation_id'   => ['nullable', 'integer', 'exists:formations,id'],
            'enrollment_id'  => ['nullable', 'integer', 'exists:enrollments,id'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'currency'       => ['required', 'string', 'max:10'],
            'payment_date'   => ['required', 'date'],
            'school_year'    => ['required', 'string', 'max:20'],
            'method'         => ['required', 'in:cash,card,bank_transfer,other'],
            'reference'      => ['nullable', 'string', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ];
    }
}
