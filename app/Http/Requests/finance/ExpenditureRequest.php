<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ExpenditureRequest extends FormRequest
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
            'id_kontak'        => ['required', 'integer', 'exists:mysql.kontak,id_kontak'],
            'id_journal'       => ['required', 'integer', 'exists:finance.journal,id_journal'],
            'date'             => ['required', 'date'],
            'pay_type'         => ['required', 'string'],
            'record_type'      => ['required', 'string'],
            'description'      => ['required', 'string'],
            'reference_number' => ['required', 'string'],
            'in_ex_tax'        => ['required', 'string', 'in:y,n,o'],
            'total'            => ['required', 'numeric'],
            'value'            => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_kontak.required' => 'ID Kontak wajib diisi.',
            'id_kontak.integer'  => 'ID Kontak harus berupa angka.',
            'id_kontak.exists'   => 'ID Kontak tidak ditemukan dalam database.',

            'id_journal.required' => 'ID Jurnal wajib diisi.',
            'id_journal.integer'  => 'ID Jurnal harus berupa angka.',
            'id_journal.exists'   => 'ID Jurnal tidak ditemukan dalam database.',

            'date.required' => 'Tanggal wajib diisi.',
            'date.date'     => 'Tanggal harus dalam format yang valid.',

            'pay_type.required' => 'Jenis pembayaran wajib diisi.',
            'pay_type.string'   => 'Jenis pembayaran harus berupa teks.',

            'record_type.required' => 'Tipe pencatatan wajib diisi.',
            'record_type.string'   => 'Tipe pencatatan harus berupa teks.',

            'description.required' => 'Deskripsi wajib diisi.',
            'description.string'   => 'Deskripsi harus berupa teks.',

            'reference_number.required' => 'Nomor referensi wajib diisi.',
            'reference_number.string'   => 'Nomor referensi harus berupa teks.',

            'in_ex_tax.required' => 'In Ex Tax wajib diisi.',
            'in_ex_tax.string'   => 'In Ex Tax harus berupa teks.',
            'in_ex_tax.in'       => 'In Ex Tax harus berupa y atau n atau o.',

            'total.required' => 'Nilai transaksi wajib diisi.',
            'total.numeric'  => 'Nilai transaksi harus berupa angka.',

            'value.required' => 'Nilai transaksi wajib diisi.',
            'value.numeric'  => 'Nilai transaksi harus berupa angka.',
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

    public function prepareForValidation()
    {
        $fields = ['total', 'value'];
        $data = [];
        foreach ($fields as $field) {
            if ($this->has($field) && $this->$field !== null && $this->$field !== '') {
                $data[$field] = normalizeNumber($this->$field);
            }
        }
        $this->merge($data);
    }
}
