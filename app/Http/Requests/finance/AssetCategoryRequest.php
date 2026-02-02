<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AssetCategoryRequest extends FormRequest
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
            'presence'       => ['required', 'string'],
            'is_depreciable' => ['required', 'integer', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Name is required',
            'name.string'             => 'Name must be a string',
            'presence.required'       => 'Presence is required',
            'presence.string'         => 'Presence must be a string',
            'is_depreciable.required' => 'Depreciable is required',
            'is_depreciable.integer'  => 'Depreciable must be an integer',
            'is_depreciable.in'       => 'Depreciable must be 0 or 1',
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
