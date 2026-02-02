<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class BankCashRequest extends FormRequest
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
            'id_coa' => ['required', 'integer', 'exists:finance.coa,id_coa', 'unique:finance.bank_n_cash,id_coa,' . $this->id_bank_n_cash . ',id_bank_n_cash'],
            'type'   => ['required', 'string', 'in:bank,cash,petty_cash'],
            'show'   => ['required', 'string', 'in:y,n'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_coa.unique'   => 'Cash/Bank Account Already Used',
            'id_coa.required' => 'Cash/Bank Account is required',
            'id_coa.exists'   => 'Cash/Bank Account does not exist',
            'id_coa.integer'  => 'Cash/Bank Account must be an integer',
            'type.required'   => 'Type is required',
            'type.in'         => 'Type must be bank, cash, petty_cash',
            'show.required'   => 'Show is required',
            'show.in'         => 'Show must be y or n',
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
