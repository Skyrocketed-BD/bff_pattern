<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KontraktorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'pic'           => $this->name,
            'name'          => $this->name,
            'username'      => $this->username,
            'id_kontraktor' => $this->id_kontraktor,
            'company'       => $this->toKontraktor->company,
            'leader'        => $this->toKontraktor->leader,
            'npwp'          => $this->toKontraktor->npwp,
            'telepon'       => $this->toKontraktor->telepon,
            'address'       => $this->toKontraktor->address,
            'postal_code'   => $this->toKontraktor->postal_code,
            'email'         => $this->toKontraktor->email,
            'website'       => $this->toKontraktor->website,
            'capital'       => $this->toKontraktor->capital,
            'color'         => $this->toKontraktor->color,
            'initial'       => $this->toKontraktor->initial,
            'created_at'    => $this->toKontraktor->created_at,
            'updated_at'    => $this->toKontraktor->updated_at,
        ];
    }
}
