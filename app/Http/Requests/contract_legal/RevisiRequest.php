<?php

namespace App\Http\Requests\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class RevisiRequest extends FormRequest
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
            'revisi_ke'         => ['required', 'string'],
            'keterangan'        => ['required', 'string'],
            'judul'             => ['required', 'string'],
            // 'files'              => ['required', 'file', 'mimes:pdf', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'revisi_ke.required'            => 'Revisi Ke wajib diisi.',
            'revisi_ke.string'              => 'Revisi Ke harus berupa teks.',
            'keterangan.required'           => 'Keterangan wajib diisi.',
            'keterangan.string'             => 'Keterangan harus berupa teks.',
            'judul.required'                => 'Setiap judul dalam array wajib diisi.',
            'judul.string'                  => 'Setiap judul harus berupa teks.',
            // 'files.required'                 => 'Setiap file dalam array wajib diunggah.',
            // 'files.file'                     => 'Setiap item dalam array harus berupa file.',
            // 'files.mimes'                    => 'Setiap file harus berformat PDF.',
            // 'files.max'                      => 'Setiap file tidak boleh lebih dari 2MB.',
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
