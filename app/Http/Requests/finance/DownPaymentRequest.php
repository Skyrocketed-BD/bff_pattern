<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class DownPaymentRequest extends FormRequest
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
            'category'         => ['required', 'string', 'in:penerimaan,pengeluaran'],
            'date'             => ['required', 'date'],
            'total'            => ['required', 'numeric'],
            'description'      => ['required', 'string'],
            'attachment'       => ['required', 'file', 'mimes:pdf'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_kontak.required'   => 'Kontak wajib diisi.',
            'id_kontak.integer'    => 'Kontak harus berupa angka.',
            'id_kontak.exists'     => 'Kontak tidak ditemukan dalam database.',
            'category.required'    => 'Kategori wajib diisi.',
            'category.string'      => 'Kategori harus berupa teks.',
            'category.in'          => 'Kategori harus berupa penerimaan atau pengeluaran.',
            'date.required'        => 'Tanggal wajib diisi.',
            'date.date'            => 'Tanggal harus dalam format yang valid.',
            'total.required'       => 'Nilai transaksi wajib diisi.',
            'total.numeric'        => 'Nilai transaksi harus berupa angka.',
            'description.required' => 'Deskripsi wajib diisi.',
            'description.string'   => 'Deskripsi harus berupa teks.',
            'attachment.required'  => 'Lampiran wajib diisi.',
            'attachment.file'      => 'Lampiran harus berupa file.',
            'attachment.mimes'     => 'Lampiran harus berupa file PDF.',
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
