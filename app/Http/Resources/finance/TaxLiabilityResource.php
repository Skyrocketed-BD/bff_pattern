<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxLiabilityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_tax_liability'   => $this->id_tax_liability,
            'transaction_number' => $this->transaction_number,
            'date'               => $this->date,
            'value'              => $this->value,
            'description'        => $this->description,
            'attachment'         => asset_upload('file/tax_liability/' . $this->attachment),
            'status'             => $this->status,
        ];
    }
}
