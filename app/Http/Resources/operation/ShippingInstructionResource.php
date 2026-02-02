<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingInstructionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response['id_shipping_instruction'] = $this->id_shipping_instruction;
        $response['id_plan_barging']         = $this->id_plan_barging;
        $response['id_kontraktor']           = $this->id_kontraktor;
        $response['kontraktor']              = $this->toKontraktor->company ?? '-';
        $response['id_slot']                 = $this->id_slot;
        $response['slot']                    = $this->toSlot->name ?? '-';
        $response['pb_name']                 = $this->toPlanBarging->pb_name ?? null;
        $response['number_si']               = $this->number_si;
        $response['consignee']               = $this->consignee;
        $response['surveyor']                = $this->surveyor;
        $response['notify_party']            = $this->notify_party;
        $response['tug_boat']                = $this->tug_boat;
        $response['barge']                   = $this->barge;
        $response['gross_tonage']            = $this->gross_tonage;
        $response['loading_port']            = $this->loading_port;
        $response['unloading_port']          = $this->unloading_port;
        $response['load_date_start']         = $this->load_date_start;
        $response['load_date_finish']        = $this->load_date_finish ?? '-';
        $response['load_amount']             = $this->load_amount;
        $response['information']             = $this->information ?? '-';
        $response['mining_inspector']        = $this->mining_inspector;
        $response['status']                  = $this->status;
        $response['color']                   = $this->toKontraktor->color ?? '-';
        $response['initial']                 = $this->toKontraktor->initial ?? '---';
        $response['shipping_method']         = $this->toPlanBarging->shipping_method ?? null;

        if (empty($this->toPlanBarging->toInvoiceFob->toInvoiceBill->toTransaction->transaction_number)) {
            $response['transaction_number'] = $this->toPlanBarging->toInvoiceFob->transaction_number ?? null; // lama
        } else {
            $response['transaction_number'] = $this->toPlanBarging->toInvoiceFob->toInvoiceBill->toTransaction->transaction_number ?? null; // baru
        }

        $response['reference_number']   = $this->toPlanBarging->toInvoiceFob->reference_number ?? null;
        $response['id_kontak']          = $this->toPlanBarging->toInvoiceFob->id_kontak ?? null;
        $response['buyer_name']         = $this->toPlanBarging->toInvoiceFob->toKontak->name ?? null;

        if ($this->toShippingInstructionApprove) {
            $response['approval'] = ShippingInstructionApproveResource::collection($this->toShippingInstructionApprove);
        }

        return $response;
    }
}
