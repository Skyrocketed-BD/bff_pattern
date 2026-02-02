<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CoaHeadRequest extends FormRequest
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
            'id_coa_group' => ['required', 'integer'],
            'name'         => ['required', 'string'],
            'coa'          => ['required', 'integer', 'digits:' . get_arrangement('coa_digit'), 'unique:finance.coa_head,coa,' . $this->id . ',id_coa_head'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_coa_group.required' => 'Coa group wajib',
            'id_coa_group.integer'  => 'Coa group harus integer',
            'name.required'         => 'Name wajib',
            'name.string'           => 'Name harus string',
            'coa.required'          => 'Coa wajib',
            'coa.integer'           => 'Coa harus nomor',
            'coa.digits'            => 'Coa harus ' . get_arrangement('coa_digit') . ' digit',
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
