<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class DateRangeRequest extends FormRequest
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
            'start_date' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:end_date'],
            'end_date'   => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required'        => 'Start date is required',
            'start_date.date'            => 'Start date must be a valid date',
            'start_date.date_format'     => 'Start date must be in the format YYYY-MM-DD',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date',
            'end_date.required'          => 'End date is required',
            'end_date.date'              => 'End date must be a valid date',
            'end_date.date_format'       => 'End date must be in the format YYYY-MM-DD',
            'end_date.after_or_equal'    => 'End date must be after or equal to start date',
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
