<?php

namespace App\Http\Requests;

use App\Classes\ApiResponseClass;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class PushyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Token is required',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        return ApiResponseClass::throw('Validation errors', 422, $validator->errors());
    }
}
