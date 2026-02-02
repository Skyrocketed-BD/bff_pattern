<?php

namespace App\Http\Requests\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class KasusRiwayatNlRequest extends FormRequest
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
            'id_kasus_nl'   => ['required', 'integer', 'exists:contract_legal.kasus_nl,id_kasus_nl'],
            'id_tahapan_nl' => ['required', 'integer', 'exists:contract_legal.tahapan_nl,id_tahapan_nl'],
            'nama'          => ['required', 'string'],
            'tanggal'       => ['required', 'date'],
            'deskripsi'     => ['required', 'string'],
            'judul'         => ['required', 'array'],
            'judul.*'       => ['required', 'string'],
            'file'          => ['required', 'array'],
            'file.*'        => ['required', 'file', 'mimes:pdf', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_kasus_nl.required'   => 'ID kasus non-litigasi wajib diisi.',
            'id_kasus_nl.integer'    => 'ID kasus non-litigasi harus berupa angka.',
            'id_kasus_nl.exists'     => 'ID kasus non-litigasi tidak ditemukan dalam database.',
            'id_tahapan_nl.required' => 'ID tahapan non-litigasi wajib diisi.',
            'id_tahapan_nl.integer'  => 'ID tahapan non-litigasi harus berupa angka.',
            'id_tahapan_nl.exists'   => 'ID tahapan non-litigasi tidak ditemukan dalam database.',
            'nama.required'          => 'Nama wajib diisi.',
            'nama.string'            => 'Nama harus berupa teks.',
            'tanggal.required'       => 'Tanggal wajib diisi.',
            'tanggal.date'           => 'Tanggal harus dalam format yang valid.',
            'deskripsi.required'     => 'Deskripsi wajib diisi.',
            'deskripsi.string'       => 'Deskripsi harus berupa teks.',
            'judul.required'         => 'Judul wajib diisi.',
            'judul.array'            => 'Judul harus berupa array.',
            'judul.*.required'       => 'Setiap judul dalam array wajib diisi.',
            'judul.*.string'         => 'Setiap judul harus berupa teks.',
            'file.required'          => 'File wajib diunggah.',
            'file.array'             => 'File harus berupa array.',
            'file.*.required'        => 'Setiap file dalam array wajib diunggah.',
            'file.*.file'            => 'Setiap item dalam array harus berupa file.',
            'file.*.mimes'           => 'Setiap file harus berformat PDF.',
            'file.*.max'             => 'Setiap file tidak boleh lebih dari 2MB.',
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
