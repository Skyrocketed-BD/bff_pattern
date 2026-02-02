<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_asset_item'       => $this->id_asset_item,
            'id_asset_coa'        => $this->toAssetHead->id_asset_coa,
            'id_asset_group'      => $this->toAssetHead->id_asset_group,
            'asset_number'        => $this->asset_number,
            'name_asset_group'    => $this->toAssetHead->toAssetGroup->name,
            'id_asset_category'   => $this->toAssetHead->id_asset_category,
            'name_asset_category' => $this->toAssetHead->toAssetCategory->name,
            'name'                => $this->toAssetHead->name,
            'tgl'                 => $this->toAssetHead->tgl,
            'identity_number'     => $this->identity_number,
            'price'               => $this->price,
            'total'               => $this->total,
            'attachment'          => asset_upload('file/asset_item/' . $this->attachment),
        ];
    }
}
