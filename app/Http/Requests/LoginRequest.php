<?php

namespace App\Http\Requests;

use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Response;

class LoginRequest extends FormRequest
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
            'username' => ['required'],
            'password' => ['required', 'min:8', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib',
            'password.required' => 'Password wajib',
            'password.min'      => 'Password must be at least 8 characters',
            'password.max'      => 'Password must not exceed 50 characters',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errorMessages = collect($validator->errors()->messages())
            ->map(fn($messages) => $messages[0])
            ->toArray();

        ActivityLogHelper::log('validation_error', 0, $errorMessages);

        $response = [
            'errors'  => $validator->errors(),
            'message' => 'Validation errors',
            'status'  => false
        ];

        throw new HttpResponseException(Response::make($response, 422));
    }
}
