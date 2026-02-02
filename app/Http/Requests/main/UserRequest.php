<?php

namespace App\Http\Requests\main;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'id_role'  => ['required', 'integer'],
            'name'     => ['required', 'string'],
            'username' => ['required', 'string', 'min:4', 'unique:users,username,' . $this->id . ',id_users'],
            'email'    => ['required', 'email', 'unique:users,email,' . $this->id . ',id_users'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_role.required'  => 'Role wajib',
            'name.required'     => 'Name wajib',
            'username.required' => 'Username wajib',
            'username.unique'   => 'Username is already taken',
            'username.min'      => 'Username must be at least 4 characters',
            'email.required'    => 'Email wajib',
            'email.email'       => 'Email is invalid',
            'email.unique'      => 'Email is already taken',
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
