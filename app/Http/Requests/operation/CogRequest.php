<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CogRequest extends FormRequest
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
            'type' => ['required', 'string'],
            'min'  => ['required', 'numeric', 'min:0'],
            'max'  => ['required', 'numeric', 'gt:min'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Type wajib',
            'min.required'  => 'Min wajib',
            'max.required'  => 'Max wajib',
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
