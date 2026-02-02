<?php

namespace App\Http\Requests\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class KasusRiwayatLRequest extends FormRequest
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
            'id_kasus_l'   => ['required', 'integer', 'exists:contract_legal.kasus_l,id_kasus_l'],
            'id_tahapan_l' => ['required', 'integer', 'exists:contract_legal.tahapan_l,id_tahapan_l'],
            'nama'         => ['required', 'string'],
            'tanggal'      => ['required', 'date'],
            'deskripsi'    => ['required', 'string'],
            'judul'        => ['required', 'array'],
            'judul.*'      => ['required', 'string'],
            'file'         => ['required', 'array'],
            'file.*'       => ['required', 'file', 'mimes:pdf', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_kasus_l.required'   => 'ID kasus litigasi wajib diisi.',
            'id_kasus_l.integer'    => 'ID kasus litigasi harus berupa angka.',
            'id_kasus_l.exists'     => 'ID kasus litigasi tidak ditemukan dalam database.',
            'id_tahapan_l.required' => 'ID tahapan litigasi wajib diisi.',
            'id_tahapan_l.integer'  => 'ID tahapan litigasi harus berupa angka.',
            'id_tahapan_l.exists'   => 'ID tahapan litigasi tidak ditemukan dalam database.',
            'nama.required'         => 'Nama wajib diisi.',
            'nama.string'           => 'Nama harus berupa teks.',
            'tanggal.required'      => 'Tanggal wajib diisi.',
            'tanggal.date'          => 'Tanggal harus dalam format yang valid.',
            'deskripsi.required'    => 'Deskripsi wajib diisi.',
            'deskripsi.string'      => 'Deskripsi harus berupa teks.',
            'judul.required'        => 'Judul wajib diisi.',
            'judul.array'           => 'Judul harus berupa array.',
            'judul.*.required'      => 'Setiap judul dalam array wajib diisi.',
            'judul.*.string'        => 'Setiap judul harus berupa teks.',
            'file.required'         => 'File wajib diunggah.',
            'file.array'            => 'File harus berupa array.',
            'file.*.required'       => 'Setiap file dalam array wajib diunggah.',
            'file.*.file'           => 'Setiap item dalam array harus berupa file.',
            'file.*.mimes'          => 'Setiap file harus berformat PDF.',
            'file.*.max'            => 'Setiap file tidak boleh lebih dari 2MB.',
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
