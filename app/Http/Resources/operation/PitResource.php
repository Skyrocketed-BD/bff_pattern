<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_pit'        => $this->id_pit,
            'id_block'      => $this->id_block,
            'id_kontraktor' => $this->id_kontraktor,
            'block'         => $this->toBlock->name,
            'name'          => $this->name
        ];
    }
}
