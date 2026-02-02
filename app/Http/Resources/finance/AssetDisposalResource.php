<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetDisposalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_asset_disposal' => $this->id_asset_disposal,
            'id_asset_item'     => $this->id_asset_item,
            'date'              => $this->date,
            'description'       => $this->description,
            'attachment'        => $this->attachment,
            'transaction_number'=> $this->transaction_number,
            'status'            => $this->status,
        ];
    }
}
