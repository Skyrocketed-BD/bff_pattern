<?php

namespace App\Http\Requests\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class FinalizeRequest extends FormRequest
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
            'no_kontrak' => ['required', 'string'],
            'tgl_mulai'  => ['required', 'date'],
            'tgl_akhir'  => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_kontrak.required'    => 'Nomor kontrak wajib diisi.',
            'no_kontrak.string'      => 'Nomor kontrak harus berupa teks.',

            'tgl_mulai.required'     => 'Tanggal mulai wajib diisi.',
            'tgl_mulai.date'         => 'Tanggal mulai harus berupa format tanggal yang valid.',

            'tgl_akhir.required'     => 'Tanggal akhir wajib diisi.',
            'tgl_akhir.date'         => 'Tanggal akhir harus berupa format tanggal yang valid.',
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
