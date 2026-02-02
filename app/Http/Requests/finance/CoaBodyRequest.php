<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class CoaBodyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        return [
            'id_coa_head' => ['required', 'integer'],
            'name'        => ['required', 'string'],
            'coa'         => ['required', 'integer', 'digits:' . get_arrangement('coa_digit'), 'unique:finance.coa_body,coa,' . $this->id . ',id_coa_body', 'unique:finance.coa_head,coa'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_coa_head.required' => 'Coa head wajib',
            'id_coa_head.integer'  => 'Coa head harus integer',
            'name.required'        => 'Name wajib',
            'name.string'          => 'Name harus string',
            'coa.required'         => 'Coa wajib',
            'coa.integer'          => 'Coa harus nomor',
            'coa.unique'           => 'Coa sudah digunakan',
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
