<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoaBodyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_coa_body'          => $this->id_coa_body,
            'id_coa_head'          => $this->id_coa_head,
            'id_coa_clasification' => $this->id_coa_clasification,
            'name'                 => $this->name,
            'coa'                  => $this->coa,
        ];
    }
}
