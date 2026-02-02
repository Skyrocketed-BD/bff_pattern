<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Models\operation\ShippingInstruction;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ShippingInstructionRequest extends FormRequest
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
        $rule['id_plan_barging'] = ['required', 'integer', 'exists:operation.plan_bargings,id_plan_barging', 'unique:operation.shipping_instructions,id_plan_barging,' . $this->id . ',id_shipping_instruction'];

        // tambah by kontraktor
        if ($this->isMethod('post')) {
            $rule['consignee']       = ['required', 'string'];
            $rule['notify_party']    = ['required', 'string'];
            $rule['surveyor']        = ['required', 'string'];
            $rule['tug_boat']        = ['required', 'string'];
            $rule['barge']           = ['required', 'string'];
            $rule['gross_tonage']    = ['required', 'integer'];
            $rule['loading_port']    = ['required', 'string'];
            $rule['unloading_port']  = ['required', 'string'];
            $rule['load_date_start'] = ['required', 'date'];
            $rule['load_amount']     = ['required', 'integer'];
            $rule['attachment']      = ['required', 'file', 'mimes:pdf'];
        }

        if ($this->isMethod('put')) {
            $shipping_instruction = ShippingInstruction::find($this->id);

            if ($this->user()->id_role !== 6) {
                // ubah by kontraktor
                $rule['consignee']       = ['required', 'string'];
                $rule['notify_party']    = ['required', 'string'];
                $rule['surveyor']        = ['required', 'string'];
                $rule['tug_boat']        = ['required', 'string'];
                $rule['barge']           = ['required', 'string'];
                $rule['gross_tonage']    = ['required', 'integer'];
                $rule['loading_port']    = ['required', 'string'];
                $rule['unloading_port']  = ['required', 'string'];
                $rule['load_date_start'] = ['required', 'date'];
                $rule['load_amount']     = ['required', 'integer'];
                $rule['attachment']      = ['required', 'file', 'mimes:pdf'];
            } else {
                // ubah by teknisi (fajar)
                if ($shipping_instruction->status !== '3') {
                    $rule['consignee']        = ['required', 'string'];
                    $rule['notify_party']     = ['required', 'string'];
                    $rule['tug_boat']         = ['required', 'string'];
                    $rule['barge']            = ['required', 'string'];
                    $rule['gross_tonage']     = ['required', 'integer'];
                    $rule['loading_port']     = ['required', 'string'];
                    $rule['unloading_port']   = ['required', 'string'];
                    $rule['load_date_start']  = ['required', 'date'];
                    $rule['load_amount']      = ['required', 'integer'];
                } else {
                    $rule['id_slot']          = ['required', 'integer', 'exists:operation.slots,id_slot'];
                    $rule['consignee']        = ['required', 'string'];
                    $rule['notify_party']     = ['required', 'string'];
                    $rule['tug_boat']         = ['required', 'string'];
                    $rule['barge']            = ['required', 'string'];
                    $rule['gross_tonage']     = ['required', 'integer'];
                    $rule['loading_port']     = ['required', 'string'];
                    $rule['unloading_port']   = ['required', 'string'];
                    $rule['load_date_start']  = ['required', 'date'];
                    $rule['load_date_finish'] = ['required', 'date'];
                    $rule['load_amount']      = ['required', 'integer'];
                    $rule['information']      = ['required', 'string'];
                    $rule['mining_inspector'] = ['required', 'string'];
                    $rule['color']            = ['required', 'string'];
                }
            }
        }

        return $rule;
    }

    public function messages(): array
    {
        return [
            'id_plan_barging.required' => 'ID Rencana Barging wajib diisi.',
            'id_plan_barging.integer'  => 'ID Rencana Barging harus berupa angka.',
            'id_plan_barging.exists'   => 'ID Rencana Barging yang dipilih tidak valid.',
            'id_plan_barging.unique'   => 'ID Rencana Barging sudah digunakan dalam Instruksi Pengiriman lain.',

            'id_slot.required' => 'ID Slot wajib diisi.',
            'id_slot.integer'  => 'ID Slot harus berupa angka.',
            'id_slot.exists'   => 'ID Slot yang dipilih tidak valid.',

            'consignee.required' => 'Consignee wajib diisi.',
            'consignee.string'   => 'Consignee harus berupa teks.',

            'notify_party.required' => 'Notify Party wajib diisi.',
            'notify_party.string'   => 'Notify Party harus berupa teks.',

            'surveyor.required' => 'Surveyor wajib diisi.',
            'surveyor.string'   => 'Surveyor harus berupa teks.',

            'tug_boat.required' => 'Tug Boat wajib diisi.',
            'tug_boat.string'   => 'Tug Boat harus berupa teks.',

            'barge.required' => 'Barge wajib diisi.',
            'barge.string'   => 'Barge harus berupa teks.',

            'gross_tonage.required' => 'Gross Tonage wajib diisi.',
            'gross_tonage.integer'  => 'Gross Tonage harus berupa angka.',

            'loading_port.required' => 'Pelabuhan muat wajib diisi.',
            'loading_port.string'   => 'Pelabuhan muat harus berupa teks.',

            'unloading_port.required' => 'Pelabuhan bongkar wajib diisi.',
            'unloading_port.string'   => 'Pelabuhan bongkar harus berupa teks.',

            'load_date_start.required' => 'Tanggal mulai muat wajib diisi.',
            'load_date_start.date'     => 'Tanggal mulai muat harus berupa format tanggal yang valid.',

            'load_date_finish.required' => 'Tanggal selesai muat wajib diisi.',
            'load_date_finish.date'     => 'Tanggal selesai muat harus berupa format tanggal yang valid.',

            'load_amount.required' => 'Jumlah muatan wajib diisi.',
            'load_amount.integer'  => 'Jumlah muatan harus berupa angka.',

            'information.required' => 'Informasi wajib diisi.',
            'information.string'   => 'Informasi harus berupa teks.',

            'mining_inspector.required' => 'Inspektur tambang wajib diisi.',
            'mining_inspector.string'   => 'Inspektur tambang harus berupa teks.',

            'color.required' => 'Warna wajib diisi.',
            'color.string'   => 'Warna harus berupa teks.',

            'attachment.required' => 'Lampiran wajib diunggah.',
            'attachment.file'     => 'Lampiran harus berupa file yang valid.',
            'attachment.mimes'    => 'Lampiran harus berformat PDF.',
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
