<?php

namespace App\Http\Requests\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class KasusLRequest extends FormRequest
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
            'no'         => ['required', 'string'],
            'nama'       => ['required', 'string'],
            'tanggal'    => ['required', 'date'],
            'keterangan' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'no.required'         => 'Nomor wajib diisi.',
            'no.string'           => 'Nomor harus berupa teks.',
            'nama.required'       => 'Nama wajib diisi.',
            'nama.string'         => 'Nama harus berupa teks.',
            'tanggal.required'    => 'Tanggal wajib diisi.',
            'tanggal.date'        => 'Format tanggal tidak valid.',
            'keterangan.required' => 'Keterangan wajib diisi.',
            'keterangan.string'   => 'Keterangan harus berupa teks.',
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
