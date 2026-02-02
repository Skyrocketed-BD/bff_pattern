<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_slot'  => $this->id_slot,
            'id_jetty' => $this->id_jetty,
            'jetty'    => $this->toJetty->name,
            'name'     => $this->name,
        ];
    }
}
