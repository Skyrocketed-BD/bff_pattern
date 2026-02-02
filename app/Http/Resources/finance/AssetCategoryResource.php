<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $child = [];
        foreach ($this->toAssetHead as $key => $value) {
            $child[] = $value->toAssetItem->count();
        }

        $result = [
            'id_asset_category' => $this->id_asset_category,
            'name'              => $this->name,
            'child'             => array_sum($child),
            'presence'          => $this->presence,
            'is_depreciable'    => $this->is_depreciable,
        ];

        return $result;
    }
}
