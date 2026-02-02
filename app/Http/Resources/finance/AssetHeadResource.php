<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetHeadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $result['id_asset_head']      = $this->id_asset_head;
        $result['id_asset_group']     = $this->id_asset_group;
        $result['group']              = $this->toAssetGroup->name;
        $result['id_asset_category']  = $this->id_asset_category;
        $result['category']           = $this->toAssetCategory->name;
        $result['name']               = $this->name;
        $result['tgl']                = $this->tgl;
        $result['transaction_number'] = ($this->id_transaction ? $this->toTransaction->transaction_number : ($this->id_transaction_full ? $this->toTransactionFull->transaction_number : null));

        if ($this->toAssetItem->count() > 0) {
            $result['asset_item'] = AssetItemResource::collection($this->toAssetItem);
        }

        return $result;
    }
}
