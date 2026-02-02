<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AssetItemRequest extends FormRequest
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
            'id_asset_coa'      => ['required', 'integer', 'exists:finance.asset_coa,id_asset_coa'],
            'id_asset_group'    => ['required', 'integer', 'exists:finance.asset_group,id_asset_group'],
            'id_asset_category' => ['required', 'integer', 'exists:finance.asset_category,id_asset_category'],
            'name'              => ['required', 'string'],
            'tgl'               => ['required', 'date'],
            'price'             => ['required', 'integer'],
            'identity_number'   => ['required', 'array'],
            // 'attachment'        => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_asset_coa.required'      => 'ID COA is required',
            'id_asset_coa.integer'       => 'ID COA must be an integer',
            'id_asset_coa.exists'        => 'ID COA does not exist',
            'id_asset_group.required'    => 'ID Asset Group is required',
            'id_asset_group.integer'     => 'ID Asset Group must be an integer',
            'id_asset_group.exists'      => 'ID Asset Group does not exist',
            'id_asset_category.required' => 'ID Asset Category is required',
            'id_asset_category.integer'  => 'ID Asset Category must be an integer',
            'id_asset_category.exists'   => 'ID Asset Category does not exist',
            'name.required'              => 'Name is required',
            'name.string'                => 'Name must be a string',
            'tgl.required'               => 'Tanggal is required',
            'tgl.date'                   => 'Tanggal must be a valid date',
            'price.required'             => 'Price is required',
            'price.integer'              => 'Price must be an integer',
            'identity_number.required'   => 'Identity Number is required',
            // 'attachment.required'        => 'Attachment is required',
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
