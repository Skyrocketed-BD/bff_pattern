<?php

namespace App\Http\Requests\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class LampiranKontrakRequest extends FormRequest
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
            'judul'         => ['required', 'array'],
            'judul.*'       => ['required', 'string'],
            'file'          => ['required', 'array'],
            'file.*'        => ['required', 'file', 'mimes:pdf', 'max:25600'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required'         => 'Judul wajib diisi.',
            'judul.array'            => 'Judul harus berupa array.',
            'judul.*.required'       => 'Setiap judul dalam array wajib diisi.',
            'judul.*.string'         => 'Setiap judul harus berupa teks.',
            'file.required'          => 'File wajib diunggah.',
            'file.array'             => 'File harus berupa array.',
            'file.*.required'        => 'Setiap file dalam array wajib diunggah.',
            'file.*.file'            => 'Setiap item dalam array harus berupa file.',
            'file.*.mimes'           => 'Setiap file harus berformat PDF.',
            'file.*.max'             => 'Setiap file tidak boleh lebih dari 25MB.',
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
