<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxRateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_tax_rate'    => $this->id_tax_rate,
            'id_tax'         => (int) $this->id_tax,
            'tax'            => $this->toTax->name,
            'kd_tax'         => $this->kd_tax,
            'name'           => $this->name,
            'rate'           => $this->rate,
            'ref'            => $this->ref,
            'effective_date' => $this->effective_date
        ];
    }
}
