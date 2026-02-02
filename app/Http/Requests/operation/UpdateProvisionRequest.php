<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProvisionRequest extends FormRequest
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
            'attachment_dsr'        => 'nullable|mimes:pdf',
            'attachment_pnbp_prov'  => 'nullable|mimes:pdf',
            'attachment_coa'        => 'nullable|mimes:pdf',
            'attachment_pnbp_final' => 'nullable|mimes:pdf',
        ];
    }

    public function messages(): array
    {
        return [
            'attachment_dsr.mimes'        => 'Attachment DSR harus berupa file PDF.',
            'attachment_pnbp_prov.mimes'  => 'Attachment PNBP Prov harus berupa file PDF.',
            'attachment_coa.mimes'        => 'Attachment COA harus berupa file PDF.',
            'attachment_pnbp_final.mimes' => 'Attachment PNBP Final harus berupa file PDF.',
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
