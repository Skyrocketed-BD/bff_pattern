<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TransactionFullRequest extends FormRequest
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
            'id_journal'          => ['required', 'integer', 'exists:finance.journal,id_journal'],
            'id_kontak'           => ['required', 'integer', 'exists:mysql.kontak,id_kontak'],
            'id_transaction_bill' => ['sometimes', 'nullable', 'integer', 'exists:finance.transaction_bills,id_transaction_bill'],
            'invoice_number'      => ['required', 'string'],
            'efaktur_number'      => ['required', 'string'],
            'date'                => ['required', 'date'],
            'from_or_to'          => ['required', 'string'],
            'total'               => ['required', 'numeric'],
            'description'         => ['required', 'string'],
            'category'            => ['required', 'string'],
            'record_type'         => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_journal.required'         => 'Id journal wajib',
            'id_journal.integer'          => 'Id journal harus berupa angka',
            'id_journal.exists'           => 'Id journal tidak ditemukan',
            'id_kontak.required'          => 'Id kontak wajib',
            'id_kontak.integer'           => 'Id kontak harus berupa angka',
            'id_kontak.exists'            => 'Id kontak tidak ditemukan',
            'id_transaction_bill.integer' => 'Id transaction bill harus berupa angka',
            'id_transaction_bill.exists'  => 'Id transaction bill tidak ditemukan',
            'invoice_number.required'     => 'Invoice number wajib',
            'invoice_number.string'       => 'Invoice number harus berupa string',
            'efaktur_number.required'     => 'E faktur number wajib',
            'efaktur_number.string'       => 'E faktur number harus berupa string',
            'date.required'               => 'Date wajib',
            'date.date'                   => 'Date harus berupa date',
            'from_or_to.required'         => 'From or to wajib',
            'from_or_to.string'           => 'From or to harus berupa string',
            'total.required'              => 'Total wajib',
            'total.numeric'               => 'Total harus berupa angka',
            'description.required'        => 'Description wajib',
            'description.string'          => 'Description harus berupa string',
            'category.required'           => 'Category wajib',
            'category.string'             => 'Category harus berupa string',
            'record_type.required'        => 'Record type wajib',
            'record_type.string'          => 'Record type harus berupa string',
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
