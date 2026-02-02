<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvisionCoaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_provision_coa' => $this->id_provision_coa,
            'id_provision'     => $this->id_provision,
            
            'inv_provision'  => $this->toProvision->inv_provision,
            'method_sales'   => $this->toProvision->method_sales,
            'departure_date' => $this->toProvision->departure_date,
            'pnbp_provision' => $this->toProvision->pnbp_provision,
            'selling_price'  => $this->toProvision->selling_price,

            'inv_final'             => $this->no_invoice,
            'method_coa'            => $this->method_coa,
            'attachment'            => $this->attachment ? asset_upload('file/provision-coa/' . $this->attachment) : null,
            'attachment_pnbp_final' => $this->attachment_pnbp_final ? asset_upload('file/provision-coa/' . $this->attachment_pnbp_final) : null,
            'date'                  => $this->date,
            'price'                 => $this->price,
            'pay_pnbp'              => $this->pay_pnbp,
            'ni_final'              => $this->ni_final,
            'fe_final'              => $this->fe_final,
            'co_final'              => $this->co_final,
            'sio2_final'            => $this->sio2_final,
            'mgo2_final'            => $this->mgo2_final,
            'mc_final'              => $this->mc_final,
            'tonage_final'          => $this->tonage_final,
            'description'           => $this->description,
        ];
    }
}
