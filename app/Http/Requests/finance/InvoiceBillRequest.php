<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class InvoiceBillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_kontak'        => ['required', 'integer', 'exists:mysql.kontak,id_kontak'],
            'id_journal'       => ['sometimes', 'nullable', 'integer', 'exists:finance.journal,id_journal'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'inv_date'         => ['required', 'date', 'date_format:Y-m-d'],
            'due_date'         => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:inv_date'],
            'total'            => ['required', 'integer', 'min:0'],
            'description'      => ['nullable', 'string', 'max:65535'],
            'category'         => ['required', 'in:penerimaan,pengeluaran'],
            'type'             => ['required', 'in:transaction,transaction_full,down_payment,advance_payment'],
            'in_ex'            => ['required', 'in:y,n,o'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_kontak.required'          => 'Contact ID is required.',
            'id_kontak.integer'           => 'Contact ID must be a number.',
            'id_kontak.exists'            => 'Contact not found.',
            'id_journal.required'         => 'Journal ID is required.',
            'id_journal.integer'          => 'Journal ID must be a number.',
            'id_journal.exists'           => 'Journal not found.',
            'reference_number.string'     => 'Reference number must be text.',
            'reference_number.max'        => 'Reference number is too long (max 255 characters).',
            'inv_date.required'           => 'Invoice date is required.',
            'inv_date.date'               => 'Invalid invoice date format.',
            'inv_date.date_format'        => 'Invoice date must be in YYYY-MM-DD format (e.g., 2025-01-01).',
            'due_date.required'           => 'Due date is required.',
            'due_date.date'               => 'Invalid due date format.',
            'due_date.date_format'        => 'Due date must be in YYYY-MM-DD format (e.g., 2025-01-30).',
            'due_date.after_or_equal'     => 'Due date must be equal to or after the invoice date.',
            'total.required'              => 'Total amount is required.',
            'total.integer'               => 'Total amount must be a whole number.',
            'total.min'                   => 'Total amount cannot be negative.',
            'description.string'          => 'Description must be text.',
            'description.max'             => 'Description is too long (max 65,535 characters).',
            'category.required'           => 'Category is required.',
            'category.in'                 => 'Category must be: penerimaan (income) or pengeluaran (expense).',
            'type.required'               => 'Transaction type is required.',
            'type.in'                     => 'Invalid transaction type. Choose: transaction, transaction_full, down_payment, or advance_payment.',
            'in_ex.required'              => 'Tax type is required.',
            'in_ex.in'                    => 'Tax type must be: y (include tax), n (exclude tax), or o (other).',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errorMessages = collect($validator->errors()->messages())
            ->map(fn($messages) => $messages[0])
            ->toArray();

        ActivityLogHelper::log('validation_error', 0, $errorMessages);

        return ApiResponseClass::throw('Validation errors', 422, $validator->errors());
    }
}
