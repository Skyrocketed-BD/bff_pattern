<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InfrastructureRequest extends FormRequest
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
            'name'     => ['required', 'string'],
            'category' => ['required', 'string', Rule::in([
                'sedimen_pond',
                'nursery',
                'lab',
                'mess',
                'office',
                'workshop',
                'fuel_storage',
                'stockpile_eto_efo',
                'dome',
                'water_channel',
                'hauling'
            ])],
        ];
    
        if ($this->isMethod('post')) {
            $rules['file'] = ['required'];
        }
    
        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Name wajib',
            'name.string'       => 'Name must be a string',
            'file.required'     => 'File wajib',
            'category.required' => 'Category wajib',
            'category.string'   => 'Category must be a string',
            'category.in'       => 'Category must be one of sedimen_pond, nursery, lab, mess, office, workshop, fuel_storage, stockpile_eto_efo, dome, water_channel',
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
