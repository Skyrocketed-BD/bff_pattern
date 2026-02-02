<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if (!$this->has('payment')) {
            $this->merge(['payment' => 'no']);
        }

        if (is_array($this->payment)) {
            $payment = collect($this->payment)->map(fn ($row) => (array) $row)->toArray();
            $this->merge(['payment' => $payment]);
        }

        if (is_array($this->transaction)) {
            $transaction = collect($this->transaction)->map(fn ($row) => (array) $row)->toArray();
            $this->merge(['transaction' => $transaction]);

            $firstTransaction = $transaction[0] ?? null;
            $this->merge(['is_data_beban' => !empty($firstTransaction['dataBeban'] ?? [])]);
            if ($this->is_data_beban) {
                $dataBeban = collect($firstTransaction['dataBeban'])->map(fn ($row) => (array) $row)->toArray();
                $this->merge(['transaction' => [
                    [
                        ...$firstTransaction,
                        'dataBeban' => $dataBeban,
                    ],
                    ...array_slice($transaction, 1),
                ]]);
            }
        } else {
            $this->merge(['is_data_beban' => !empty($this->dataBeban)]);
            if ($this->is_data_beban) {
                $dataBeban = collect($this->dataBeban)->map(fn ($row) => (array) $row)->toArray();
                $this->merge(['dataBeban' => $dataBeban]);
            }
        }
    }


    public function rules(): array
    {
        if (is_string($this->payment)) {
            return [
                'payment'             => ['required', 'string', 'in:no,dp,asset,dp_asset'],
                'date'                => ['required', 'date'],
                'from_or_to'          => ['required', 'string'],
                'id_journal'          => ['required', 'integer', 'exists:finance.journal,id_journal'],
                'reference_number'    => ['required', 'string'],
                'id_transaction_type' => ['required', 'integer'],
                'description'         => ['required', 'string'],
                'discount'            => ['required', 'numeric'],
                'diskonBy'            => ['required', 'string', 'in:percentage,rupiah'],
                'total'               => ['required', 'numeric'],
                'sisa'                => ['required', 'numeric'],
                'id_kontak'           => ['required', 'integer', 'exists:mysql.kontak,id_kontak'],
                'in_ex_tax'           => ['required', 'string', 'in:y,n,o'],
                'category'            => ['required', 'string', 'in:penerimaan,pengeluaran'],
                'dataBeban'           => ['nullable', 'array'],
                'dataBeban.*.coa'     => ['nullable', 'integer', 'exists:finance.coa,coa'],
                'dataBeban.*.posisi'  => ['nullable', 'string', 'in:D,K'],
                'dataBeban.*.amount'  => ['nullable', 'numeric'],

            ];
        } elseif (is_array($this->payment)) {
            return [
                'category'                          => ['required', 'string', 'in:receipt,expenditure'],
                'payment'                           => ['required', 'array'],
                'payment.*.id_journal'              => ['required', 'integer', 'exists:finance.journal,id_journal'],
                'payment.*.total'                   => ['required', 'numeric'],
                'payment.*.date'                    => ['required', 'date'],
                'payment.*.reference_number'        => ['required', 'string'],
                'payment.*.description'             => ['required', 'string'],
                'payment.*.pay_type'                => ['required', 'string', 'in:c,cc,bt'],
                'payment.*.record_type'             => ['required', 'string', 'in:bank,cash,petty_cash'],
                'transaction'                       => ['required', 'array'],
                'transaction.*.payment'             => ['required', 'string', 'in:no,dp,asset,dp_asset'],
                'transaction.*.date'                => ['required', 'date'],
                'transaction.*.from_or_to'          => ['required', 'string'],
                'transaction.*.category'            => ['required', 'string', 'in:penerimaan,pengeluaran'],
                'transaction.*.id_journal'          => ['required', 'integer', 'exists:finance.journal,id_journal'],
                'transaction.*.reference_number'    => ['required', 'string'],
                'transaction.*.id_transaction_type' => ['required', 'integer'],
                'transaction.*.description'         => ['required', 'string'],
                'transaction.*.discount'            => ['required', 'numeric'],
                'transaction.*.diskonBy'            => ['required', 'string', 'in:percentage,rupiah'],
                'transaction.*.total'               => ['required', 'numeric'],
                'transaction.*.sisa'                => ['required', 'numeric'],
                'transaction.*.id_kontak'           => ['required', 'integer', 'exists:mysql.kontak,id_kontak'],
                'transaction.*.in_ex_tax'           => ['required', 'string'],
                'transaction.*.fundSource'          => ['required', 'string', 'in:bank,cash,petty_cash'],
                'transaction.*.journalPayment'      => ['required', 'integer', 'exists:finance.journal,id_journal'],
                'transaction.*.totalPayment'        => ['required', 'numeric'],
                'transaction.*.dataBeban'           => ['nullable', 'array'],
                'transaction.*.dataBeban.*.coa'     => ['nullable', 'integer', 'exists:finance.coa,coa'],
                'transaction.*.dataBeban.*.posisi'  => ['nullable', 'string', 'in:D,K'],
                'transaction.*.dataBeban.*.amount'  => ['nullable', 'numeric'],
            ];
        }

        return [];
    }

    public function messages(): array
    {
        return [
            'payment.required'             => 'Payment wajib diisi.',
            'payment.string'               => 'Payment harus berupa teks.',
            'payment.in'                   => 'Payment harus berupa no, dp, asset atau dp_asset.',
            'date.required'                => 'Tanggal wajib diisi.',
            'from_or_to.required'          => 'Recipient wajib diisi.',
            'from_or_to.string'            => 'Recipient harus berupa teks.',
            'category.required'            => 'Category wajib diisi.',
            'category.string'              => 'Category harus berupa teks.',
            'category.in'                  => 'Category harus berupa penerimaan atau pengeluaran.',
            'id_journal.required'          => 'Journal wajib diisi.',
            'id_journal.integer'           => 'ID Journal harus berupa angka.',
            'id_journal.exists'            => 'Journal tidak ditemukan.',
            'reference_number.required'    => 'Reference Number wajib diisi.',
            'reference_number.string'      => 'Reference Number harus berupa teks.',
            'id_transaction_type.required' => 'Transaction Type wajib diisi.',
            'id_transaction_type.integer'  => 'ID Transaction Type harus berupa angka.',
            'description.required'         => 'Description wajib diisi.',
            'description.string'           => 'Description harus berupa teks.',
            'discount.required'            => 'Discount wajib diisi.',
            'discount.numeric'             => 'Discount harus berupa angka.',
            'diskonBy.required'            => 'Diskon By wajib diisi.',
            'diskonBy.string'              => 'Diskon By harus berupa teks.',
            'diskonBy.in'                  => 'Diskon By harus berupa percentage atau rupiah.',
            'total.required'               => 'Total wajib diisi.',
            'total.numeric'                => 'Total harus berupa angka.',
            'sisa.required'                => 'Sisa wajib diisi.',
            'sisa.numeric'                 => 'Sisa harus berupa angka.',
            'id_kontak.required'           => 'Kontak wajib diisi.',
            'id_kontak.integer'            => 'ID Kontak harus berupa angka.',
            'id_kontak.exists'             => 'Kontak tidak ditemukan.',
            'in_ex_tax.required'           => 'In Ex Tax wajib diisi.',
            'in_ex_tax.string'             => 'In Ex Tax harus berupa teks.',
            'in_ex_tax.in'                 => 'In Ex Tax harus berupa y atau n atau o.',
            'dataBeban.array'              => 'Data Beban harus berupa array.',
            'dataBeban.*.coa.integer'      => 'ID COA harus berupa angka.',
            'dataBeban.*.coa.exists'       => 'COA tidak ditemukan.',
            'dataBeban.*.posisi.string'    => 'Posisi harus berupa teks.',
            'dataBeban.*.posisi.in'        => 'Posisi harus berupa D atau K.',
            'dataBeban.*.amount.numeric'   => 'Amount harus berupa angka.',

            'category.required'                   => 'Category wajib diisi.',
            'category.string'                     => 'Category harus berupa teks.',
            'category.in'                         => 'Category harus berupa receipt atau expenditure.',
            'payment.required'                    => 'Payment wajib diisi.',
            'payment.array'                       => 'Payment harus berupa array.',
            'payment.*.id_journal.required'       => 'Journal wajib diisi.',
            'payment.*.id_journal.integer'        => 'ID Journal harus berupa angka.',
            'payment.*.id_journal.exists'         => 'Journal tidak ditemukan.',
            'payment.*.total.required'            => 'Total wajib diisi.',
            'payment.*.total.numeric'             => 'Total harus berupa angka.',
            'payment.*.date.required'             => 'Date wajib diisi.',
            'payment.*.date.date'                 => 'Date harus dalam format yang valid.',
            'payment.*.reference_number.required' => 'Reference Number wajib diisi.',
            'payment.*.reference_number.string'   => 'Reference Number harus berupa teks.',
            'payment.*.description.required'      => 'Description wajib diisi.',
            'payment.*.description.string'        => 'Description harus berupa teks.',
            'payment.*.pay_type.required'         => 'Pay Type wajib diisi.',
            'payment.*.pay_type.string'           => 'Pay Type harus berupa teks.',
            'payment.*.pay_type.in'               => 'Pay Type harus berupa c , cc atau bt.',
            'payment.*.record_type.required'      => 'Record Type wajib diisi.',
            'payment.*.record_type.string'        => 'Record Type harus berupa teks.',
            'payment.*.record_type.in'            => 'Record Type harus berupa bank, cash atau petty_cash',

            'transaction.required'                       => 'Transaction wajib diisi.',
            'transaction.array'                          => 'Transaction harus berupa array.',
            'transaction.*.payment.required'             => 'Payment wajib diisi.',
            'transaction.*.payment.string'               => 'Payment harus berupa teks.',
            'transaction.*.payment.in'                   => 'Payment harus berupa no, dp, asset atau dp_asset.',
            'transaction.*.date.required'                => 'Tanggal wajib diisi.',
            'transaction.*.from_or_to.required'          => 'Recipient wajib diisi.',
            'transaction.*.from_or_to.string'            => 'Recipient harus berupa teks.',
            'transaction.*.category.required'            => 'Category wajib diisi.',
            'transaction.*.category.string'              => 'Category harus berupa teks.',
            'transaction.*.category.in'                  => 'Category harus berupa penerimaan atau pengeluaran.',
            'transaction.*.id_journal.required'          => 'Journal wajib diisi.',
            'transaction.*.id_journal.integer'           => 'ID Journal harus berupa angka.',
            'transaction.*.id_journal.exists'            => 'Journal tidak ditemukan.',
            'transaction.*.reference_number.required'    => 'Reference Number wajib diisi.',
            'transaction.*.reference_number.string'      => 'Reference Number harus berupa teks.',
            'transaction.*.id_transaction_type.required' => 'Transaction Type wajib diisi.',
            'transaction.*.id_transaction_type.integer'  => 'ID Transaction Type harus berupa angka.',
            'transaction.*.description.required'         => 'Description wajib diisi.',
            'transaction.*.description.string'           => 'Description harus berupa teks.',
            'transaction.*.discount.required'            => 'Discount wajib diisi.',
            'transaction.*.discount.numeric'             => 'Discount harus berupa angka.',
            'transaction.*.diskonBy.required'            => 'Diskon By wajib diisi.',
            'transaction.*.diskonBy.string'              => 'Diskon By harus berupa teks.',
            'transaction.*.diskonBy.in'                  => 'Diskon By harus berupa percentage atau rupiah.',
            'transaction.*.total.required'               => 'Total wajib diisi.',
            'transaction.*.total.numeric'                => 'Total harus berupa angka.',
            'transaction.*.sisa.required'                => 'Sisa wajib diisi.',
            'transaction.*.sisa.numeric'                 => 'Sisa harus berupa angka.',
            'transaction.*.id_kontak.required'           => 'Kontak wajib diisi.',
            'transaction.*.id_kontak.integer'            => 'ID Kontak harus berupa angka.',
            'transaction.*.id_kontak.exists'             => 'Kontak tidak ditemukan.',
            'transaction.*.in_ex_tax.required'           => 'In Ex Tax wajib diisi.',
            'transaction.*.in_ex_tax.string'             => 'In Ex Tax harus berupa teks.',
            'transaction.*.in_ex_tax.in'                 => 'In Ex Tax harus berupa y atau n.',
            'transaction.*.fundSource.required'          => 'Fund Source wajib diisi.',
            'transaction.*.fundSource.string'            => 'Fund Source harus berupa teks.',
            'transaction.*.fundSource.in'                => 'Fund Source harus berupa bank, cash atau petty_cash.',
            'transaction.*.journalPayment.required'      => 'Journal Payment wajib diisi.',
            'transaction.*.journalPayment.string'        => 'Journal Payment harus berupa teks.',
            'transaction.*.totalPayment.required'        => 'Total Payment wajib diisi.',
            'transaction.*.totalPayment.numeric'         => 'Total Payment harus berupa angka.',
            'transaction.*.dataBeban.array'              => 'Data Beban harus berupa array.',
            'transaction.*.dataBeban.*.coa.integer'      => 'COA harus berupa angka.',
            'transaction.*.dataBeban.*.coa.exists'       => 'COA tidak ditemukan.',
            'transaction.*.dataBeban.*.posisi.string'    => 'Posisi harus berupa teks.',
            'transaction.*.dataBeban.*.posisi.in'        => 'Posisi harus berupa D atau K.',
            'transaction.*.dataBeban.*.amount.numeric'   => 'Amount harus berupa angka.',
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
