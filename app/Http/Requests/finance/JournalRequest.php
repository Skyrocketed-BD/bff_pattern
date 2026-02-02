<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class JournalRequest extends FormRequest
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
            'name'           => ['required', 'string'],
            'category'       => ['required', 'string'],
            'alocation'      => ['required', 'string'],
            'is_outstanding' => ['required', 'string'],
            'id_tax_rate'    => ['required', 'array'],
            'id_coa'         => ['required', 'array'],
            'id_coa.*'       => ['required', 'integer', 'exists:finance.coa,id_coa'],
            'type'           => ['required', 'array'],
            'type.*'         => ['required', 'string', 'in:D,K'],
            'open_input'     => ['required', 'array'],
            'open_input.*'   => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Name wajib',
            'name.string'             => 'Name harus string',
            'category.required'       => 'Category wajib',
            'category.string'         => 'Category harus string',
            'alocation.required'      => 'Alocation wajib',
            'alocation.string'        => 'Alocation harus string',
            'is_outstanding.required' => 'Is Outstanding wajib',
            'is_outstanding.string'   => 'Is Outstanding harus string',
            'id_tax_rate.required'    => 'Tax Rate wajib',
            'id_tax_rate.array'       => 'Tax Rate harus array',
            'id_coa.required'         => 'Coa wajib',
            'id_coa.array'            => 'Coa harus array',
            'id_coa.*.required'       => 'Coa wajib',
            'id_coa.*.integer'        => 'Coa harus integer',
            'id_coa.*.exists'         => 'Coa tidak ada',
            'type.required'           => 'Type wajib',
            'type.array'              => 'Type harus array',
            'type.*.required'         => 'Type wajib',
            'type.*.string'           => 'Type harus string',
            'type.*.in'               => 'Type harus D atau K',
            'open_input.required'     => 'Open Input wajib',
            'open_input.array'        => 'Open Input harus array',
            'open_input.*.required'   => 'Open Input wajib',
            'open_input.*.string'     => 'Open Input harus string',
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
