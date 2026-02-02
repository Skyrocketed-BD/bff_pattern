<?php

namespace App\Http\Requests\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class KontrakTahapanRequest extends FormRequest
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
            'tgl'           => ['date'],
            'keterangan'    => ['required', 'string'],
            'judul'         => ['required', 'string'],
            'files.File'    => ['required', 'file', 'mimes:pdf', 'max:25600'],
        ];
    }

    public function messages(): array
    {
        return [
            'tgl.date'               => 'Format tanggal tidak valid.',
            'keterangan.required'    => 'Keterangan wajib diisi.',
            'keterangan.string'      => 'Keterangan harus berupa teks.',
            'judul.required'         => 'Judul wajib diisi.',
            'judul.string'           => 'Judul harus berupa teks.',
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
