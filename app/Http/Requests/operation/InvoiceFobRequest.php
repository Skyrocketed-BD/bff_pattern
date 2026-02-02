<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceFobRequest extends FormRequest
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
            'id_plan_barging' => [
                'required',
                'integer',
                'exists:operation.plan_bargings,id_plan_barging',
                Rule::unique('operation.invoice_fob', 'id_plan_barging')
                    ->ignore($this->route('id'), 'id_invoice_fob'),
            ],
            'id_journal'       => ['required', 'integer', 'exists:finance.journal,id_journal'],
            'id_kontak'        => ['required', 'integer', 'exists:mysql.kontak,id_kontak'],
            'date'             => ['required', 'date'],
            'description'      => ['required', 'string'],
            'reference_number' => ['nullable', 'string', 'max:255'],

            'hpm'             => ['required', 'numeric'],
            'hma'             => ['required', 'numeric'],
            'kurs'            => ['required', 'numeric'],
            'ni'              => ['required', 'numeric'],
            'mc'              => ['required', 'numeric'],
            'tonage'          => ['required', 'numeric'],
            'price'           => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_plan_barging.required' => 'Plan barging is required.',
            'id_plan_barging.integer'  => 'Invalid plan barging ID.',
            'id_plan_barging.exists'   => 'Plan barging not found.',
            'id_plan_barging.unique'   => 'Plan barging already invoiced.',
            'id_journal.required'      => 'Journal is required.',
            'id_journal.integer'       => 'Invalid journal ID.',
            'id_journal.exists'        => 'Journal not found.',
            'id_kontak.required'       => 'Contact is required.',
            'id_kontak.integer'        => 'Invalid contact ID.',
            'id_kontak.exists'         => 'Contact not found.',
            'date.required'            => 'Date is required.',
            'date.date'                => 'Invalid date.',
            'description.required'     => 'Description is required.',
            'description.string'       => 'Description must be text.',
            'reference_number.string'  => 'Reference number must be text.',
            'reference_number.max'     => 'Reference number too long (max 255 characters).',

            'hpm.required'    => 'HPM is required.',
            'hpm.numeric'     => 'HPM must be a number.',
            'hma.required'    => 'HMA is required.',
            'hma.numeric'     => 'HMA must be a number.',
            'kurs.required'   => 'Exchange rate is required.',
            'kurs.numeric'    => 'Exchange rate must be a number.',
            'ni.required'     => 'Nickel Index is required.',
            'ni.numeric'      => 'Nickel Index must be a number.',
            'mc.required'     => 'Moisture Content is required.',
            'mc.numeric'      => 'Moisture Content must be a number.',
            'tonage.required' => 'Tonage is required.',
            'tonage.numeric'  => 'Tonage must be a number.',
            'price.required'  => 'Price is required.',
            'price.numeric'   => 'Price must be a number.',
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
            'hpm'    => normalizeNumber($this->hpm),
            'hma'    => normalizeNumber($this->hma),
            'kurs'   => normalizeNumber($this->kurs),
            'ni'     => normalizeNumber($this->ni),
            'mc'     => normalizeNumber($this->mc),
            'tonage' => normalizeNumber($this->tonage),
            'price'  => normalizeNumber($this->price),
        ]);
    }
}
