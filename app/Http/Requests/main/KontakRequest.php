<?php

namespace App\Http\Requests\main;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class KontakRequest extends FormRequest
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
        $id_kontak = $this->id;

        return [
            'id_kontak_jenis' => 'required',
            'name'            => 'required',
            'npwp'            => [
                'required',
                'digits:16',
                'numeric',
                Rule::unique('kontak', 'npwp')
                    ->ignore($id_kontak, 'id_kontak')
                    ->where(function ($query) {
                        return $query->where('npwp', '!=', '0000000000000000');
                    })
            ],
            'phone'           => 'required',
            'email'           => 'required|email',
            'address'         => 'required',
            'postal_code'     => 'required',
            'is_company'      => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'id_kontak_jenis.required' => 'Contact Type is required',
            'name.required'            => 'Name is required',
            'npwp.required'            => 'NPWP is required',
            'npwp.digits'              => 'NPWP must consist of 16 digits',
            'npwp.numeric'             => 'NPWP must only contain numbers',
            'npwp.unique'              => 'NPWP has already been taken',
            'phone.required'           => 'Phone is required',
            'email.required'           => 'Email is required',
            'email.email'              => 'Email must be valid',
            'address.required'         => 'Address is required',
            'postal_code.required'     => 'Postal Code is required',
            'is_company.required'      => 'Is Company is required',
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

    public function prepareForValidation()
    {
        $this->merge([
            'npwp' => sanitize_npwp($this->npwp),
        ]);
    }
}
