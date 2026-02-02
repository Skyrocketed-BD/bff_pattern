<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class KontraktorRequest extends FormRequest
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
            'name'    => ['required', 'string'],
            'email'   => ['required', 'string', 'unique:users,username,' . Auth::user()->id_users . ',id_users'],
            'leader'  => ['required', 'string'],
            'npwp'    => ['required', 'string', 'min:16', 'max:16'],
            'telepon' => ['required', 'string'],
            'address' => ['required', 'string'],
            'capital' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Name wajib',
            'email.required'   => 'Email wajib',
            'email.email'      => 'Email is invalid',
            'email.unique'     => 'Email is already taken',
            'leader.required'  => 'Leader wajib',
            'npwp.required'    => 'NPWP wajib',
            'telepon.required' => 'Telepon wajib',
            'address.required' => 'Address wajib',
            'capital.required' => 'Capital wajib',
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
