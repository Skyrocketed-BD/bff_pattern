<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_cog'        => $this->id_cog,
            'id_kontraktor' => $this->id_kontraktor,
            'max'           => $this->max,
            'min'           => $this->min,
            'type'          => $this->type,
            'kontraktor'    => $this->toKontraktor->company,
        ];
    }
}
