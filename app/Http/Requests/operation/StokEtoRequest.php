<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StokEtoRequest extends FormRequest
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
            $rules['dome_name']             = ['required', 'string', 'unique:operation.dom_eto,name'];
            $rules['date_in']               = ['required', 'date'];
            $rules['tonage_after']          = ['required', 'numeric'];
            $rules['mining_recovery_type']  = ['required', 'string'];
            $rules['mining_recovery_value'] = ['required', 'numeric'];
            $rules['ni']                    = ['required', 'numeric'];
            $rules['fe']                    = ['required', 'numeric'];
            $rules['co']                    = ['required', 'numeric'];
            $rules['sio2']                  = ['required', 'numeric'];
            $rules['mgo2']                  = ['required', 'numeric'];
            $rules['tonage']                = ['required', 'numeric'];
            $rules['ritasi']                = ['required', 'numeric'];
            $rules['attachment']            = ['required', 'file', 'mimes:pdf'];
            $rules['id_stok_in_pit']        = ['array', 'min:1'];
        }

        if ($this->isMethod('put')) {
            $rules['date_in']               = ['required', 'date'];
            $rules['tonage_after']          = ['required', 'numeric'];
            $rules['mining_recovery_type']  = ['required', 'string'];
            $rules['mining_recovery_value'] = ['required', 'numeric'];
            $rules['ni']                    = ['required', 'numeric'];
            $rules['fe']                    = ['required', 'numeric'];
            $rules['co']                    = ['required', 'numeric'];
            $rules['sio2']                  = ['required', 'numeric'];
            $rules['mgo2']                  = ['required', 'numeric'];
            $rules['tonage']                = ['required', 'numeric'];
            $rules['ritasi']                = ['required', 'numeric'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'dome_name.required' => 'Nama dome wajib diisi.',
            'dome_name.string'   => 'Nama dome harus berupa teks.',
            'dome_name.unique'   => 'Nama dome sudah ada.',

            'date_in.required'               => 'Tanggal masuk wajib diisi.',
            'date_in.date'                   => 'Tanggal masuk harus berupa format tanggal yang valid.',
            'tonage_after.required'          => 'Tonase setelah wajib diisi.',
            'tonage_after.numeric'           => 'Tonase setelah harus berupa angka bulat.',
            'mining_recovery_type.required'  => 'Jenis pemulihan tambang wajib diisi.',
            'mining_recovery_type.string'    => 'Jenis pemulihan tambang harus berupa teks.',
            'mining_recovery_value.required' => 'Nilai pemulihan tambang wajib diisi.',
            'mining_recovery_value.numeric'  => 'Nilai pemulihan tambang harus berupa angka.',
            'attachment.required'            => 'Lampiran wajib diunggah.',
            'attachment.file'                => 'Lampiran harus berupa file.',
            'attachment.mimes'               => 'Lampiran harus berupa file dengan format PDF.',
            'ni.required'                    => 'Nilai Ni wajib diisi.',
            'ni.numeric'                     => 'Nilai Ni harus berupa angka.',
            'fe.required'                    => 'Nilai Fe wajib diisi.',
            'fe.numeric'                     => 'Nilai Fe harus berupa angka.',
            'co.required'                    => 'Nilai Co wajib diisi.',
            'co.numeric'                     => 'Nilai Co harus berupa angka.',
            'sio2.required'                  => 'Nilai SiO2 wajib diisi.',
            'sio2.numeric'                   => 'Nilai SiO2 harus berupa angka.',
            'mgo2.required'                  => 'Nilai MgO2 wajib diisi.',
            'mgo2.numeric'                   => 'Nilai MgO2 harus berupa angka.',
            'tonage.required'                => 'Tonase wajib diisi.',
            'tonage.numeric'                 => 'Tonase harus berupa angka.',
            'ritasi.required'                => 'Ritasi wajib diisi.',
            'ritasi.numeric'                 => 'Ritasi harus berupa angka.',
            'id_stok_in_pit.array'           => 'ID stok in pit harus berupa array.',
            'id_stok_in_pit.min'             => 'Minimal harus ada satu ID stok in pit.',
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
