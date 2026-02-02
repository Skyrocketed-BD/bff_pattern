<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CoaMultipleRequest extends FormRequest
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
            'coaName'     => ['required', 'array'],
            'coaNumber'   => ['required', 'array'],
            'coaName.*'   => ['required', 'string'],
            'coaNumber.*' => ['required', 'integer', 'digits:' . get_arrangement('coa_digit'), 'unique:finance.coa,coa,' . $this->id . ',id_coa', 'unique:finance.coa_body,coa', 'unique:finance.coa_head,coa'],
            'id_coa_body' => ['required', 'integer'],
        ];
    }

    // 'coa' => 'required|array',
    public function messages(): array
    {
        return [
            'coaName.required'   => 'Name wajib',
            'coaName.string'     => 'Name harus string',
            'coaNumber.required' => 'Coa wajib',
            'coaNumber.integer'  => 'Coa harus nomor',
            'coaNumber.digits'   => 'Coa harus ' . get_arrangement('coa_digit') . ' digit',
            'coaNumber.unique'   => 'Coa sudah digunakan',
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
