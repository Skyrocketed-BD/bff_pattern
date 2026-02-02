<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DomEtoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id_dom_eto,
            'id_dom_eto'    => $this->id_dom_eto,
            'id_kontraktor' => $this->id_kontraktor,
            'name'          => $this->name
        ];
    }
}
