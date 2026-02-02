<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ProvisionCoaRequest extends FormRequest
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
        if ($this->isMethod('post')) {
            $rule['id_provision'] = ['required', 'integer'];
            $rule['no_invoice']   = ['required', 'string'];
            $rule['attachment']   = ['required', 'file', 'mimes:pdf'];
            $rule['date']         = ['required', 'date'];

            $rule['hpm']        = ['required', 'numeric'];
            $rule['hma']        = ['required', 'numeric'];
            $rule['kurs']       = ['required', 'numeric'];
            $rule['price']      = ['required', 'numeric'];
            $rule['pay_pnbp']   = ['required', 'numeric'];
            $rule['ni_final']   = ['required', 'numeric'];
            $rule['fe_final']   = ['required', 'numeric'];
            $rule['co_final']   = ['required', 'numeric'];
            $rule['sio2_final'] = ['required', 'numeric'];
            $rule['mgo2_final'] = ['required', 'numeric'];
            $rule['mc_final']   = ['required', 'numeric'];
        }

        if ($this->isMethod('put')) {
            $rule['attachment_pnbp_final'] = ['required', 'file', 'mimes:pdf'];
            $rule['pay_pnbp']              = ['required', 'numeric'];
        }

        return $rule;
    }

    public function messages(): array
    {
        return [
            'id_provision.required'          => 'ID Provision wajib diisi.',
            'id_provision.integer'           => 'ID Provision harus berupa angka.',
            'no_invoice.required'            => 'Nomor invoice wajib diisi.',
            'no_invoice.string'              => 'Nomor invoice harus berupa teks.',
            'attachment.required'            => 'Lampiran wajib diunggah.',
            'attachment.file'                => 'Lampiran harus berupa file.',
            'attachment.mimes'               => 'Lampiran harus berupa file PDF.',
            'date.required'                  => 'Tanggal wajib diisi.',
            'date.date'                      => 'Format tanggal tidak valid.',
            'hpm.required'                   => 'HPM wajib diisi.',
            'hpm.numeric'                    => 'HPM harus berupa angka.',
            'hma.required'                   => 'HMA wajib diisi.',
            'hma.numeric'                    => 'HMA harus berupa angka.',
            'kurs.required'                  => 'Kurs wajib diisi.',
            'kurs.numeric'                   => 'Kurs harus berupa angka.',
            'price.required'                 => 'Harga wajib diisi.',
            'price.numeric'                  => 'Harga harus berupa angka.',
            'pay_pnbp.required'              => 'Pembayaran PNBP wajib diisi.',
            'pay_pnbp.numeric'               => 'Pembayaran PNBP harus berupa angka.',
            'ni_final.required'              => 'Ni Final wajib diisi.',
            'ni_final.numeric'               => 'Ni Final harus berupa angka.',
            'fe_final.required'              => 'Fe Final wajib diisi.',
            'fe_final.numeric'               => 'Fe Final harus berupa angka.',
            'co_final.required'              => 'Co Final wajib diisi.',
            'co_final.numeric'               => 'Co Final harus berupa angka.',
            'sio2_final.required'            => 'SiO2 Final wajib diisi.',
            'sio2_final.numeric'             => 'SiO2 Final harus berupa angka.',
            'mgo2_final.required'            => 'MgO2 Final wajib diisi.',
            'mgo2_final.numeric'             => 'MgO2 Final harus berupa angka.',
            'mc_final.required'              => 'MC Final wajib diisi.',
            'mc_final.numeric'               => 'MC Final harus berupa angka.',
            'attachment_pnbp_final.required' => 'Lampiran PNBP Final wajib diunggah.',
            'attachment_pnbp_final.file'     => 'Lampiran PNBP Final harus berupa file.',
            'attachment_pnbp_final.mimes'    => 'Lampiran PNBP Final harus berupa file PDF.',
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
        $fields = ['hpm', 'hma', 'kurs', 'price', 'pay_pnbp', 'ni_final', 'fe_final', 'co_final', 'sio2_final', 'mgo2_final', 'mc_final'];
        $data = [];
        foreach ($fields as $field) {
            if ($this->has($field) && $this->$field !== null && $this->$field !== '') {
                $data[$field] = normalizeNumber($this->$field);
            }
        }
        $this->merge($data);
    }
}
