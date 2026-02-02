<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DomInPitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id_dom_in_pit,
            'id_dom_in_pit' => $this->id_dom_in_pit,
            'id_kontraktor' => $this->id_kontraktor,
            'id_pit'        => $this->id_pit,
            'pit'           => $this->toPit->name,
            'name'          => $this->name
        ];
    }
}
