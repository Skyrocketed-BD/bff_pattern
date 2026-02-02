<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TaxRateRequest extends FormRequest
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
            'id_tax' => ['required', 'integer'],
            'kd_tax' => ['required', 'string', 'unique:finance.tax_rate,kd_tax,' . $this->id . ',id_tax_rate'],
            'name'   => ['required', 'string'],
            'rate'   => ['required', 'numeric'],
            'ref'    => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_tax.required' => 'Id tax wajib',
            'id_tax.integer'  => 'Id tax harus berupa angka',
            'kd_tax.required' => 'Kd tax wajib',
            'kd_tax.string'   => 'Kd tax harus berupa string',
            'kd_tax.unique'   => 'Kd tax sudah ada',
            'name.required'   => 'Name wajib',
            'name.string'     => 'Name harus berupa string',
            'rate.required'   => 'Rate wajib',
            'rate.numeric'    => 'Rate harus berupa angka',
            'ref.string'      => 'Ref harus berupa string',
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
