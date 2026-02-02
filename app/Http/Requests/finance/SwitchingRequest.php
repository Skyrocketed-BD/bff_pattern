<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SwitchingRequest extends FormRequest
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
            'date'        => ['required', 'date'],
            'description' => ['required', 'string'],
            'ammount'      => ['required', 'numeric'],
            'coa'         => ['required', 'array'],
            'coa.*'       => ['required', 'integer', 'exists:finance.coa,coa'],
            'type'        => ['required', 'array'],
            'type.*'      => ['required', 'string', 'in:K,D'],
            'id_source'   => ['required', 'integer', 'exists:finance.coa,id_coa'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'        => 'Date wajib diisi.',
            'date.date'            => 'Date harus dalam format yang valid.',
            'description.required' => 'Description wajib diisi.',
            'description.string'   => 'Description harus berupa teks.',
            'ammount.required'     => 'Amount wajib diisi.',
            'ammount.numeric'      => 'Amount harus berupa angka.',
            'coa.required'         => 'COA wajib diisi.',
            'coa.array'            => 'COA harus berupa array.',
            'coa.*.required'       => 'Value COA wajib diisi.',
            'coa.*.integer'        => 'Value COA harus berupa angka.',
            'coa.*.exists'         => 'COA tidak ditemukan dalam database.',
            'type.required'        => 'Type wajib diisi.',
            'type.array'           => 'Type harus berupa array.',
            'type.*.required'      => 'Value Type wajib diisi.',
            'type.*.string'        => 'Value Type harus berupa teks.',
            'type.*.in'            => 'Value Type harus berupa K atau D.',
            'id_source.required'   => 'ID Source wajib diisi.',
            'id_source.integer'    => 'ID Source harus berupa angka.',
            'id_source.exists'     => 'ID Source tidak ditemukan dalam database.',
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
