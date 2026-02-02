<?php

namespace App\Http\Resources\contract_legal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TahapanLResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id_tahapan_l,
            'id_tahapan_l' => $this->id_tahapan_l,
            'name'         => $this->name,
            'category'     => $this->category
        ];
    }
}
