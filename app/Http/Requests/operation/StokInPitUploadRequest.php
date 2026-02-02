<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StokInPitUploadRequest extends FormRequest
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
            'id_block'      => ['required'],
            'id_pit'        => ['required'],
            'id_dom_in_pit' => ['required'],
            'date'          => ['required', 'date'],
            'file'          => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_block.required'      => 'Block wajib',
            'id_pit.required'        => 'Pit wajib',
            'id_dom_in_pit.required' => 'Dom wajib',
            'date.required'          => 'Date wajib',
            'date.date'              => 'Date tidak valid',
            'file.required'          => 'File wajib',
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
