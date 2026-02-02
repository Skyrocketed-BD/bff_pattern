<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoaHeadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_coa_head'  => $this->id_coa_head,
            'id_coa_group' => $this->id_coa_group,
            'group'        => $this->toCoaGroup->name,
            'name'         => $this->name,
            'coa'          => $this->coa,
            'default'      => $this->default
        ];
    }
}
