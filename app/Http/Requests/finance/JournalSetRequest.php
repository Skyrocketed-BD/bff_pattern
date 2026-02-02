<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class JournalSetRequest extends FormRequest
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
            'id_journal' => ['required', 'integer', 'exists:finance.journal,id_journal'],
            'id_coa'     => ['required', 'integer', 'exists:finance.coa,id_coa'],
            'type'       => ['required', 'string', 'in:K,D'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_journal.required' => 'ID Journal wajib diisi.',
            'id_journal.integer'  => 'ID Journal harus berupa angka.',
            'id_journal.exists'   => 'ID Journal tidak ditemukan dalam database.',

            'id_coa.required' => 'ID COA wajib diisi.',
            'id_coa.integer'  => 'ID COA harus berupa angka.',
            'id_coa.exists'   => 'ID COA tidak ditemukan dalam database.',

            'type.required' => 'Tipe wajib diisi.',
            'type.string'   => 'Tipe harus berupa teks.',
            'type.in'       => 'Tipe harus berupa K atau D.',
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
