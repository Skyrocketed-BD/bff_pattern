<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AssetPurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_kontak'        => ['required', 'integer', 'exists:mysql.kontak,id_kontak'],
            'id_journal'       => ['required', 'integer', 'exists:finance.journal,id_journal'],
            'reference_number' => ['required', 'string'],
            'date'             => ['required', 'date'],
            'total'            => ['required', 'numeric'],
            'description'      => ['nullable', 'string'],
              // 'is_outstanding' => ['required', 'boolean'],
              // 'in_ex_tax' => ['required', 'boolean'],
            'details'                     => ['required', 'array'],
            'details.*.id_asset_coa'      => ['required', 'integer'],
            'details.*.id_asset_group'    => ['required', 'integer'],
            'details.*.id_asset_category' => ['required', 'integer'],
            'details.*.name'              => ['required', 'string'],
            'details.*.price'             => ['required', 'numeric'],
            'details.*.identity_number'   => ['required', 'array'],
            'details.*.identity_number.*' => ['required', 'string'],
            'details.*.attachment'        => ['nullable', 'array'],
            'details.*.attachment.*'      => ['nullable', 'file']
        ];
    }

    public function messages(): array
    {
        return [
            'id_kontak.required'        => 'Kontak is required',
            'id_kontak.integer'         => 'Kontak must be an integer',
            'id_kontak.exists'          => 'Kontak does not exist',
            'id_journal.required'       => 'Journal is required',
            'id_journal.integer'        => 'Journal must be an integer',
            'id_journal.exists'         => 'Journal does not exist',
            'reference_number.required' => 'Reference Number is required',
            'reference_number.string'   => 'Reference Number must be a string',
            'date.required'             => 'Date is required',
            'date.date'                 => 'Date must be a valid date',
            'total.required'            => 'Total is required',
            'total.numeric'             => 'Total must be a number',
            'description.string'        => 'Description must be a string',
              // 'is_outstanding.required' => 'Is Outstanding is required',
              // 'is_outstanding.boolean' => 'Is Outstanding must be a boolean',
              // 'in_ex_tax.required' => 'In Ex Tax is required',
              // 'in_ex_tax.boolean' => 'In Ex Tax must be a boolean',    
            'details.required'                     => 'Details are required',
            'details.array'                        => 'Details must be an array',
            'details.*.id_asset_coa.required'      => 'Asset COA is required',
            'details.*.id_asset_coa.integer'       => 'Asset COA must be an integer',
            'details.*.id_asset_group.required'    => 'Asset Group is required',
            'details.*.id_asset_group.integer'     => 'Asset Group must be an integer',
            'details.*.id_asset_category.required' => 'Asset Category is required',
            'details.*.id_asset_category.integer'  => 'Asset Category must be an integer',
            'details.*.name.required'              => 'Asset Name is required',
            'details.*.name.string'                => 'Asset Name must be a string',
            'details.*.price.required'             => 'Asset Price is required',
            'details.*.price.numeric'              => 'Asset Price must be a number',
            'details.*.identity_number.required'   => 'Identity Number is required',
            'details.*.identity_number.array'      => 'Identity Number must be an array',
            'details.*.identity_number.*.required' => 'Identity Number item is required',
            'details.*.identity_number.*.string'   => 'Identity Number item must be a string',
            'details.*.attachment.array'           => 'Attachment must be an array',
            'details.*.attachment.*.file'          => 'Attachment must be a file',
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
