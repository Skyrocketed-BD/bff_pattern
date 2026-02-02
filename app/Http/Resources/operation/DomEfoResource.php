<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DomEfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id_dom_efo,
            'id_dom_efo'    => $this->id_dom_efo,
            'id_kontraktor' => $this->id_kontraktor,
            'name'          => $this->name
        ];
    }
}
