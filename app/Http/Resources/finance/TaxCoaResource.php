<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxCoaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_tax_coa' => (int) $this->id_tax_coa,
            'id_tax'     => (int) $this->id_tax,
            'tax'        => $this->toTax->name,
            'id_coa'     => (int) $this->id_coa,
            'coa'        => $this->toCoa->name ?? '-',
            'coa_number' => $this->toCoa->coa ?? '-',
        ];
    }
}
