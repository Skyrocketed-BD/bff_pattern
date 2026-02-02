<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class JettyRequest extends FormRequest
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
        $rules = [
            'name' => ['required', 'string'],
        ];

        if($this->isMethod('post')) {
            $rules['file'] = ['required', 'mimes:json,zip,geojson'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name wajib',
            'name.string'   => 'Name must be a string',
            'file.mimes'    => 'File must be zip, geojson or json',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $errorMessages = collect($validator->errors()->messages())
            ->map(fn($messages) => $messages[0])
            ->toArray();

        ActivityLogHelper::log('validation_error', 0, $errorMessages);

        return ApiResponseClass::throw('Validation errors', 422, $validator->errors());
    }
}
