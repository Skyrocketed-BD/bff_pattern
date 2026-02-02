<?php

namespace App\Http\Requests\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class JadwalSidangRequest extends FormRequest
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
        $method = $this->method();
        if ($method === 'PUT') {
            return [
                'tgl_waktu_sidang'   => ['required', 'date'],
            ];
        } else {
            return [
                'no'                 => ['required', 'string'],
                'nama'               => ['required', 'string'],
                'tgl_waktu_sidang'   => ['required', 'date'],
                'keterangan'         => ['required', 'string'],
                'judul'              => ['required', 'array'],
                'judul.*'            => ['required', 'string'],
                'file'               => ['required', 'array'],
                'file.*'             => ['required', 'file', 'mimes:pdf', 'max:2048'],
            ];
        }
    }

    public function messages(): array
    {
        $method = $this->method();
        if ($method === 'PUT') {
            return [
                'tgl_waktu_sidang.required'   => 'Tanggal dan waktu sidang wajib diisi.',
                'tgl_waktu_sidang.date'       => 'Tanggal dan waktu sidang harus berupa format tanggal yang valid.',
            ];
        } else {
            return [
                'no.required'                 => 'Nomor wajib diisi.',
                'no.string'                   => 'Nomor harus berupa teks.',
    
                'nama.required'               => 'Nama wajib diisi.',
                'nama.string'                 => 'Nama harus berupa teks.',
    
                'tgl_waktu_sidang.required'   => 'Tanggal dan waktu sidang wajib diisi.',
                'tgl_waktu_sidang.date'       => 'Tanggal dan waktu sidang harus berupa format tanggal yang valid.',
    
                'keterangan.required'         => 'Keterangan wajib diisi.',
                'keterangan.string'           => 'Keterangan harus berupa teks.',
    
                'judul.required'              => 'Judul wajib diisi.',
                'judul.array'                 => 'Judul harus berupa array.',
                'judul.*.required'            => 'Setiap judul wajib diisi.',
                'judul.*.string'              => 'Setiap judul harus berupa teks.',
    
                'file.required'               => 'File wajib diunggah.',
                'file.array'                  => 'File harus berupa array.',
                'file.*.required'             => 'Setiap file wajib diunggah.',
                'file.*.file'                 => 'Setiap file harus berupa dokumen yang valid.',
                'file.*.mimes'                => 'Setiap file harus berformat PDF.',
                'file.*.max'                  => 'Ukuran setiap file maksimal 2MB.',
            ];
        }
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
