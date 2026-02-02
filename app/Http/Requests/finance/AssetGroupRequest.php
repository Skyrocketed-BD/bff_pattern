<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AssetGroupRequest extends FormRequest
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
            'name'              => ['required', 'string'],
            'rate'              => ['required', 'integer'],
            'benefit'           => ['required', 'string'],
            'group'             => ['required', 'string', 'in:bangunan,bukan_bangunan'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Name is required',
            'name.string'      => 'Name must be a string',
            'rate.required'    => 'Rate is required',
            'rate.integer'     => 'Rate must be an integer',
            'benefit.required' => 'Benefit is required',
            'benefit.string'   => 'Benefit must be a string',
            'group.required'   => 'Group is required',
            'group.string'     => 'Group must be a string',
            'group.in'         => 'Group must be bangunan or bukan_bangunan',
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
