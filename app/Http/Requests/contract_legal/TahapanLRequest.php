<?php

namespace App\Http\Requests\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TahapanLRequest extends FormRequest
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
            'name'     => ['required', 'string'],
            'category' => ['required', 'string', Rule::in([
                'gugatan',
                'pidana',
                'praperadilan'
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Name wajib',
            'name.string'       => 'Name must be a string',
            'category.required' => 'Category wajib',
            'category.string'   => 'Category must be a string',
            'category.in'       => 'Category must be gugatan, pidana, or praperadilan',
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
