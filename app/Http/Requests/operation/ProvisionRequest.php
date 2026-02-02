<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ProvisionRequest extends FormRequest
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
            'id_shipping_instruction' => ['required', 'integer'],
            'inv_provision'           => ['required', 'string'],
            'method_sales'            => ['required', 'string'],
            'departure_date'          => ['required', 'date'],
            'attachment'              => ['required', 'file', 'mimes:pdf'],
            'pnbp_provision'          => ['required', 'numeric'],
            'selling_price'           => ['required', 'numeric'],
            'tonage_actual'           => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_shipping_instruction.required' => 'ID shipping instruction wajib diisi.',
            'id_shipping_instruction.integer'  => 'ID shipping instruction harus berupa angka bulat.',
            'inv_provision.required'           => 'Nomor invoice provision wajib diisi.',
            'inv_provision.string'             => 'Invoice provision harus berupa teks.',
            'method_sales.required'            => 'Metode penjualan wajib diisi.',
            'method_sales.string'              => 'Metode penjualan harus berupa teks.',
            'departure_date.required'          => 'Tanggal keberangkatan wajib diisi.',
            'departure_date.date'              => 'Format tanggal keberangkatan tidak valid.',
            'attachment.required'              => 'Lampiran wajib diunggah.',
            'attachment.file'                  => 'Lampiran harus berupa file.',
            'attachment.mimes'                 => 'Lampiran harus berupa file PDF.',
            'pnbp_provision.required'          => 'Nilai PNBP provision wajib diisi.',
            'pnbp_provision.numeric'           => 'Nilai PNBP provision harus berupa angka.',
            'selling_price.required'           => 'Harga jual wajib diisi.',
            'selling_price.numeric'            => 'Harga jual harus berupa angka.',
            'tonage_actual.required'           => 'Tonase aktual wajib diisi.',
            'tonage_actual.numeric'            => 'Tonase aktual harus berupa angka.',
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
        $this->merge([
            'pnbp_provision' => normalizeNumber($this->pnbp_provision),
            'selling_price'  => normalizeNumber($this->selling_price),
            'tonage_actual'  => normalizeNumber($this->tonage_actual),
        ]);
    }
}
