<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AssetCoaRequest extends FormRequest
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
            'name'              => ['required', 'string'],
            'id_coa'            => ['required', 'integer', 'exists:finance.coa,id_coa'],
            'id_coa_acumulated' => ['required', 'integer', 'exists:finance.coa,id_coa'],
            'id_coa_expense'    => ['required', 'integer', 'exists:finance.coa,id_coa'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'              => 'Name is required',
            'name.string'                => 'Name must be a string',
            'id_coa.required'            => 'ID COA is required',
            'id_coa.integer'             => 'ID COA must be an integer',
            'id_coa.exists'              => 'ID COA does not exist',
            'id_coa_acumulated.required' => 'ID COA Acumulated is required',
            'id_coa_acumulated.integer'  => 'ID COA Acumulated must be an integer',
            'id_coa_acumulated.exists'   => 'ID COA Acumulated does not exist',
            'id_coa_expense.required'    => 'ID COA Expense is required',
            'id_coa_expense.integer'     => 'ID COA Expense must be an integer',
            'id_coa_expense.exists'      => 'ID COA Expense does not exist',
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
