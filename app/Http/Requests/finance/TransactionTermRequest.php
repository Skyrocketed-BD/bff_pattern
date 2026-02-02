<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TransactionTermRequest extends FormRequest
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
            // 'id_invoice_bill' => ['required', 'integer', 'exists:finance.invoice_bills,id_invoice_bill'],
            'nama'            => ['required', 'string'],
            'date'            => ['required', 'date'],
            'percent'         => ['required', 'numeric'],
            'value_ppn'       => ['required', 'integer'],
            'value_pph'       => ['required', 'integer'],
            'value_percent'   => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            // 'id_invoice_bill.required' => 'Invoice bill ID is required.',
            // 'id_invoice_bill.integer'  => 'Invoice bill ID must be a number.',
            // 'id_invoice_bill.exists'   => 'Invoice bill not found.',
            'nama.required'            => 'Name is required.',
            'nama.string'              => 'Name must be text.',
            'date.required'            => 'Date is required.',
            'date.date'                => 'Invalid date format.',
            'percent.required'         => 'Percentage is required.',
            'percent.numeric'          => 'Percentage must be a number.',
            'value_ppn.required'       => 'PPN (VAT) value is required.',
            'value_ppn.integer'        => 'PPN (VAT) value must be a whole number.',
            'value_pph.required'       => 'PPh (Income Tax) value is required.',
            'value_pph.integer'        => 'PPh (Income Tax) value must be a whole number.',
            'value_percent.required'   => 'Percentage value is required.',
            'value_percent.integer'    => 'Percentage value must be a whole number.',
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
