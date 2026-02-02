<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class DomEfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user()->toRole->name == 'Kontraktor') {
            return true;
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name wajib',
            'name.string'   => 'Name harus berupa string',
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

    protected function failedAuthorization()
    {
        ActivityLogHelper::log('not_authorized', 0, ['Unauthorized action']);

        return ApiResponseClass::throw('You are unauthorized for this action.', 403);
    }
}
