<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class JournalAdjustmentRequest extends FormRequest
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
        if ($this->transaction_type === 'recurring') {
            return [
                'date'             => ['required', 'date'],
                'description'      => ['required', 'string'],
                'duration'         => ['required', 'numeric'],
                'transaction_type' => ['required', 'string', 'in:one_time,recurring'],
                'reference_number' => ['required', 'string'],
                'id_coa'           => ['required', 'array'],
                'id_coa.*'         => ['required', 'integer', 'exists:finance.coa,id_coa'],
                'coa'              => ['required', 'array'],
                'coa.*'            => ['required', 'integer', 'exists:finance.coa,coa'],
                'type'             => ['required', 'array'],
                'type.*'           => ['required', 'string', 'in:K,D'],
                'amount'           => ['required', 'array'],
                'amount.*'         => ['required', 'numeric'],
            ];
        } else {
            return [
                'date'             => ['required', 'date'],
                'description'      => ['required', 'string'],
                'duration'         => ['required', 'numeric'],
                'transaction_type' => ['required', 'string', 'in:one_time,recurring'],
                'id_coa'           => ['required', 'array'],
                'id_coa.*'         => ['required', 'integer', 'exists:finance.coa,id_coa'],
                'coa'              => ['required', 'array'],
                'coa.*'            => ['required', 'integer', 'exists:finance.coa,coa'],
                'type'             => ['required', 'array'],
                'type.*'           => ['required', 'string', 'in:K,D'],
                'amount'           => ['required', 'array'],
                'amount.*'         => ['required', 'numeric'],
            ];
        }
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal wajib diisi.',
            'date.date'     => 'Tanggal harus dalam format yang valid.',

            'description.required' => 'Deskripsi wajib diisi.',
            'description.string'   => 'Deskripsi harus berupa teks.',

            'duration.required' => 'Durasi wajib diisi.',
            'duration.numeric'  => 'Durasi harus berupa angka.',

            'transaction_type.required' => 'Tipe transaksi wajib diisi.',
            'transaction_type.string'   => 'Tipe transaksi harus berupa teks.',
            'transaction_type.in'       => 'Tipe transaksi harus berupa one_time atau recurring.',

            'reference_number.required' => 'Nomor referensi wajib diisi.',
            'reference_number.string'   => 'Nomor referensi harus berupa teks.',

            'id_coa.required'  => 'ID COA wajib diisi.',
            'id_coa.array'     => 'ID COA harus berupa array.',
            'id_coa.*.integer' => 'Value ID COA harus berupa angka.',
            'id_coa.*.exists'  => 'Value ID COA tidak ditemukan dalam database.',

            'coa.required'  => 'COA wajib diisi.',
            'coa.array'     => 'COA harus berupa array.',
            'coa.*.integer' => 'Value COA harus berupa angka.',
            'coa.*.exists'  => 'Value COA tidak ditemukan dalam database.',

            'type.required' => 'Tipe wajib diisi.',
            'type.array'    => 'Tipe harus berupa array.',
            'type.*.string' => 'Value Tipe harus berupa teks.',
            'type.*.in'     => 'Value Tipe harus berupa K atau D.',

            'amount.required'  => 'Nilai wajib diisi.',
            'amount.array'     => 'Nilai harus berupa array.',
            'amount.*.numeric' => 'Value Nilai harus berupa angka.',
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
