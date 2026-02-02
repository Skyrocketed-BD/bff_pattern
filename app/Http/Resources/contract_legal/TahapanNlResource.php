<?php

namespace App\Http\Resources\contract_legal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TahapanNlResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id_tahapan_nl,
            'id_tahapan_nl' => $this->id_tahapan_nl,
            'name'          => $this->name
        ];
    }
}
