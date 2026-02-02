<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KontraktorUsersRequest extends FormRequest
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
            'name'  => ['required', 'string'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->kontraktor_user, 'id_users')->whereNull('deleted_at')
            ],
            // 'username' => [
            //     'required',
            //     'string',
            //     'min:4',
            //     Rule::unique('users', 'username')->ignore($this->kontraktor_user, 'id_users')->whereNull('deleted_at')
            // ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Nama wajib diisi',
            'email.required'    => 'Email wajib diisi',
            'email.email'       => 'Format email tidak valid',
            'email.unique'      => 'Email sudah digunakan',
            // 'username.required' => 'Username wajib diisi',
            // 'username.unique'   => 'Username sudah digunakan',
            // 'username.min'      => 'Username harus terdiri dari minimal 4 karakter',
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
