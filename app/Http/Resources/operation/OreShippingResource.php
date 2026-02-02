<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OreShippingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'key'                   => $this->key_index ?? null,
            'id_provision'          => $this->toProvision->id_provision,
            'id_kontak'             => $this->toProvision->toKontak->id_kontak ?? null,
            'pb_name'               => $this->toProvision->toShippingInstruction->toPlanBarging->pb_name ?? null,
            'number_si'             => $this->toProvision->toShippingInstruction->number_si ?? null,
            'id_kontraktor'         => $this->toProvision->toShippingInstruction->id_kontraktor,
            'kontraktor'            => $this->toProvision->toShippingInstruction->toKontraktor->company,
            'buyer'                 => $this->toProvision->toKontak->name ?? '-',
            'initial'               => $this->toProvision->toShippingInstruction->toKontraktor->initial,
            'tug_boat'              => $this->toProvision->toShippingInstruction->tug_boat,
            'barge'                 => $this->toProvision->toShippingInstruction->barge,
            'consignee'             => $this->toProvision->toShippingInstruction->consignee,
            'date'                  => $this->date,
            'departure_date'        => $this->toProvision->departure_date,
            'load_amount'           => $this->toProvision->toShippingInstruction->load_amount,
            'ni_provision'          => $this->toProvision->toShippingInstruction->toPlanBarging->toInvoiceFob->ni,
            'mc_provision'          => $this->toProvision->toShippingInstruction->toPlanBarging->toInvoiceFob->mc,
            'price_provision'       => $this->toProvision->toShippingInstruction->toPlanBarging->toInvoiceFob->price,
            'inv_final'             => $this->no_invoice,
            'ni_final'              => $this->ni_final,
            'mc_final'              => $this->mc_final,
            'tonage_final'          => $this->tonage_final,
            'price_final'           => $this->price,
            'attachment_coa'        => $this->attachment ? asset_upload('file/provision-coa/' . $this->attachment) : null,
            'attachment_pnbp_final' => $this->attachment_pnbp_final ? asset_upload('file/provision-coa/' . $this->attachment_pnbp_final) : null
        ];
    }
}
