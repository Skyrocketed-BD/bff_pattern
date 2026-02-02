<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TaxLiabilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    
    protected function prepareForValidation()
    {
        if (is_array($this->rows)) {
            $rows = collect($this->rows)->map(function ($row) {
                return (array) $row; // cast each to array
            })->toArray();

            $this->merge(['rows' => $rows]);
        }
    }

    public function rules(): array
    {
        return [
            'date'                    => ['required', 'date'],
            'description'             => ['required', 'string'],
            'id_coa_expense'          => ['required', 'integer', 'exists:finance.coa,id_coa'],
            'coa_expense'             => ['required', 'integer', 'exists:finance.coa,coa'],
            'total_expense'           => ['required', 'numeric'],
            'rows'                    => ['required', 'array', 'min:1'],
            'rows.*'                  => ['required', 'array'],
            'rows.*.id_coa'           => ['required', 'integer', 'exists:finance.coa,id_coa'],
            'rows.*.coa'              => ['required', 'integer', 'exists:finance.coa,coa'],
            'rows.*.liability_amount' => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'                    => 'Date wajib diisi.',
            'date.date'                        => 'Date harus dalam format yang valid.',
            'description.required'             => 'Description wajib diisi.',
            'description.string'               => 'Description harus berupa teks.',
            'id_coa_expense.required'          => 'ID COA Expense wajib diisi.',
            'id_coa_expense.integer'           => 'ID COA Expense harus berupa angka.',
            'id_coa_expense.exists'            => 'ID COA Expense tidak ditemukan dalam database.',
            'coa_expense.required'             => 'COA Expense wajib diisi.',
            'coa_expense.integer'              => 'COA Expense harus berupa angka.',
            'coa_expense.exists'               => 'COA Expense tidak ditemukan dalam database.',
            'total_expense.required'           => 'Total Expense wajib diisi.',
            'total_expense.numeric'            => 'Total Expense harus berupa angka.',
            'rows.required'                    => 'Rows wajib diisi.',
            'rows.array'                       => 'Rows harus berupa array.',
            'rows.min'                         => 'Rows minimal 1.',
            'rows.*.id_coa.required'           => 'ID COA wajib diisi.',
            'rows.*.id_coa.integer'            => 'ID COA harus berupa angka.',
            'rows.*.id_coa.exists'             => 'ID COA tidak ditemukan dalam database.',
            'rows.*.coa.required'              => 'COA wajib diisi.',
            'rows.*.coa.integer'               => 'COA harus berupa angka.',
            'rows.*.coa.exists'                => 'COA tidak ditemukan dalam database.',
            'rows.*.liability_amount.required' => 'Liability Amount wajib diisi.',
            'rows.*.liability_amount.numeric'  => 'Liability Amount harus berupa angka.',
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
