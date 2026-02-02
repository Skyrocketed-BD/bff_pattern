<?php

namespace App\Http\Resources\finance;

use App\Models\finance\Coa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetCoaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_asset_coa'      => $this->id_asset_coa,
            'id_coa'            => $this->id_coa ?? null,
            'id_coa_acumulated' => $this->id_coa_acumulated ?? null,
            'id_coa_expense'    => $this->id_coa_expense ?? null,
            'coa_name'          => $this->toCoa->name ?? null,
            'coa_acumulated'    => Coa::whereIdCoa($this->id_coa_acumulated)->first()->name  ?? null,
            'coa_expense'       => Coa::whereIdCoa($this->id_coa_expense)->first()->name  ?? null,
            'name'              => $this->name
        ];
    }
}
