<?php

namespace App\Http\Requests\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class KontrakRequest extends FormRequest
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
            'no_kontrak' => ['required', 'string', 'unique:contract_legal.kontrak,no_kontrak'],
            'tgl_mulai'  => ['required', 'date'],
            'tgl_akhir'  => ['required', 'date'],
            'files.File' => ['required', 'file', 'mimes:pdf', 'max:25600'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_kontrak.required'    => 'Nomor kontrak wajib diisi.',
            'no_kontrak.string'      => 'Nomor kontrak harus berupa teks.',
            'no_kontrak.unique'      => 'Nomor kontrak sudah terdaftar.',

            'tgl_mulai.required'     => 'Tanggal mulai wajib diisi.',
            'tgl_mulai.date'         => 'Tanggal mulai harus berupa format tanggal yang valid.',

            'tgl_akhir.required'     => 'Tanggal akhir wajib diisi.',
            'tgl_akhir.date'         => 'Tanggal akhir harus berupa format tanggal yang valid.',

            'files.File.required'    => 'File wajib diunggah.',
            'files.File.File'        => 'Item dalam array harus berupa file.',
            'files.File.mimes'       => 'File harus berformat PDF.',
            'files.File.max'         => 'File tidak boleh lebih dari 25MB.',
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
