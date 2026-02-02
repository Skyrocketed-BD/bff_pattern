<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TaxCoaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_tax' => ['required', 'integer', 'unique:finance.tax_coa,id_tax,' . $this->tax_coa . ',id_tax_coa'],
            'id_coa' => ['required', 'integer', 'unique:finance.tax_coa,id_coa,' . $this->tax_coa . ',id_tax_coa'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_tax.required' => 'Tax id wajib',
            'id_tax.integer'  => 'Tax id harus berupa angka',
            'id_coa.required' => 'Coa id wajib',
            'id_coa.integer'  => 'Coa id harus berupa angka',
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
