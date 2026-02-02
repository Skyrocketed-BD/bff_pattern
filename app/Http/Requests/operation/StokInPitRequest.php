<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StokInPitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user()->toRole->name == 'Kontraktor') {
            return true;
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'id_block'      => ['required', 'integer'],
            'id_pit'        => ['required', 'integer'],
            'id_dom_in_pit' => ['required', 'integer'],
            'date'          => ['required', 'date'],
            'ni'            => ['required', 'numeric'],
            'fe'            => ['required', 'numeric'],
            'co'            => ['required', 'numeric'],
            'sio2'          => ['required', 'numeric'],
            'mgo2'          => ['required', 'numeric'],
            'tonage'        => ['required', 'numeric'],
            'ritasi'        => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_block.required'      => 'Id Block wajib',
            'id_block.integer'       => 'Id Block tidak valid',
            'id_pit.required'        => 'Id Pit wajib',
            'id_pit.integer'         => 'Id Pit tidak valid',
            'id_dom_in_pit.required' => 'Id Dom wajib',
            'id_dom_in_pit.integer'  => 'Id Dom tidak valid',
            'date.required'          => 'Date wajib',
            'date.date'              => 'Date tidak valid',
            'ni.required'            => 'Ni wajib',
            'ni.numeric'             => 'Ni tidak valid',
            'fe.required'            => 'Fe wajib',
            'fe.numeric'             => 'Fe tidak valid',
            'co.required'            => 'Co wajib',
            'co.numeric'             => 'Co tidak valid',
            'sio2.required'          => 'Mgosio2 wajib',
            'sio2.numeric'           => 'Mgosio2 tidak valid',
            'mgo2.required'          => 'Mgomgo2 wajib',
            'mgo2.numeric'           => 'Mgomgo2 tidak valid',
            'tonage.required'        => 'Tonage wajib',
            'tonage.numeric'         => 'Tonage tidak valid',
            'ritasi.required'        => 'Ritasi wajib',
            'ritasi.numeric'         => 'Ritasi tidak valid',
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

    protected function failedAuthorization()
    {
        ActivityLogHelper::log('not_authorized', 0, ['Unauthorized action']);

        return ApiResponseClass::throw('You are unauthorized for this action.', 403);
    }
}
