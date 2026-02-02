<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_coa'      => $this->id_coa,
            'id_coa_body' => $this->id_coa_body,
            'id_tax'      => $this->toTaxCoa->id_tax ?? null,
            'name'        => $this->name,
            'coa'         => $this->coa,
            'is_beban'    => $this->toCoaBody->toCoaClasification->group === 'beban' ? true : false,
            'is_tax'      => isset($this->toTaxCoa->id_tax) ? true : false,
        ];
    }
}
