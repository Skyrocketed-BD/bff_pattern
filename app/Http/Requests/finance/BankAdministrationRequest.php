<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class BankAdministrationRequest extends FormRequest
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
            'id_coa_bank' => ['required', 'integer', 'exists:finance.coa,id_coa'],
            'date'        => ['required', 'date'],
            'description' => ['required', 'string'],
            'bank_fee'    => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_coa_bank.required' => 'ID Account Source is required',
            'id_coa_bank.integer'  => 'ID Account Source must be an integer',
            'id_coa_bank.exists'   => 'ID Account Source does not exist',
            'date.required'        => 'Date is required',
            'date.date'            => 'Date must be a valid date',
            'description.required' => 'Description is required',
            'description.string'   => 'Description must be a string',
            'bank_fee.required'    => 'Bank Fee is required',
            'bank_fee.numeric'     => 'Bank Fee must be a number',
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
