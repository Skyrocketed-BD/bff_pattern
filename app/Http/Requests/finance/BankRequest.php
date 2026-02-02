<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class BankRequest extends FormRequest
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
            'name'           => ['required', 'string'],
            'account_number' => ['required', 'numeric'],
            'account_name'   => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Name wajib',
            'name.string'             => 'Name harus berupa string',
            'account_number.required' => 'Account number wajib',
            'account_number.numeric'  => 'Account number harus berupa angka',
            'account_name.required'   => 'Account name wajib',
            'account_name.string'     => 'Account name harus berupa string',
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
