<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StokPsiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user()->toRole->name == 'Kontraktor') {
            return true;
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'date'       => ['required', 'date'],
            'attachment' => ['required', 'file', 'mimes:pdf'],

            'id_stok_eto' => ['array', 'min:1'],
            'id_stok_efo' => ['array', 'min:1'],

            'eto_ni' => ['array', 'min:1'],
            'efo_ni' => ['array', 'min:1'],

            'eto_fe' => ['array', 'min:1'],
            'efo_fe' => ['array', 'min:1'],

            'eto_co' => ['array', 'min:1'],
            'efo_co' => ['array', 'min:1'],

            'eto_sio2' => ['array', 'min:1'],
            'efo_sio2' => ['array', 'min:1'],

            'eto_mgo2' => ['array', 'min:1'],
            'efo_mgo2' => ['array', 'min:1'],

            'eto_tonage' => ['array', 'min:1'],
            'efo_tonage' => ['array', 'min:1'],

            'eto_ritasi' => ['array', 'min:1'],
            'efo_ritasi' => ['array', 'min:1'],

            'eto_mc' => ['array', 'min:1'],
            'efo_mc' => ['array', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'       => 'Date wajib',
            'date.date'           => 'Date tidak valid',
            'attachment.required' => 'Attachment wajib',
            'attachment.file'     => 'Attachment harus berupa file',
            'attachment.mimes'    => 'Attachment harus berupa file PDF',
           
            'id_stok_eto.array' => 'Id Stok Eto harus berupa array',
            'id_stok_eto.min'   => 'Id Stok Eto minimal 1',
            'id_stok_efo.array' => 'Id Stok Efo harus berupa array',
            'id_stok_efo.min'   => 'Id Stok Efo minimal 1',

            'eto_ni.array' => 'Eto Ni harus berupa array',
            'eto_ni.min'   => 'Eto Ni minimal 1',
            'efo_ni.array' => 'Efo Ni harus berupa array',
            'efo_ni.min'   => 'Efo Ni minimal 1',

            'eto_fe.array' => 'Eto Fe harus berupa array',
            'eto_fe.min'   => 'Eto Fe minimal 1',
            'efo_fe.array' => 'Efo Fe harus berupa array',
            'efo_fe.min'   => 'Efo Fe minimal 1',

            'eto_co.array' => 'Eto Co harus berupa array',
            'eto_co.min'   => 'Eto Co minimal 1',
            'efo_co.array' => 'Efo Co harus berupa array',
            'efo_co.min'   => 'Efo Co minimal 1',

            'eto_sio2.array' => 'Eto Sio2 harus berupa array',
            'eto_sio2.min'   => 'Eto Sio2 minimal 1',
            'efo_sio2.array' => 'Efo Sio2 harus berupa array',
            'efo_sio2.min'   => 'Efo Sio2 minimal 1',

            'eto_mgo2.array' => 'Eto Mgo2 harus berupa array',
            'eto_mgo2.min'   => 'Eto Mgo2 minimal 1',
            'efo_mgo2.array' => 'Efo Mgo2 harus berupa array',
            'efo_mgo2.min'   => 'Efo Mgo2 minimal 1',

            'eto_tonage.array' => 'Eto Tonage harus berupa array',
            'eto_tonage.min'   => 'Eto Tonage minimal 1',
            'efo_tonage.array' => 'Efo Tonage harus berupa array',
            'efo_tonage.min'   => 'Efo Tonage minimal 1',

            'eto_ritasi.array' => 'Eto Ritasi harus berupa array',
            'eto_ritasi.min'   => 'Eto Ritasi minimal 1',
            'efo_ritasi.array' => 'Efo Ritasi harus berupa array',
            'efo_ritasi.min'   => 'Efo Ritasi minimal 1',

            'eto_mc.array' => 'Eto Mc harus berupa array',
            'eto_mc.min'   => 'Eto Mc minimal 1',
            'efo_mc.array' => 'Efo Mc harus berupa array',
            'efo_mc.min'   => 'Efo Mc minimal 1',
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
