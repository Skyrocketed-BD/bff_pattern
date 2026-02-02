<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_block'          => $this->id_block,
            'id_kontraktor'     => $this->id_kontraktor,
            'name'              => $this->name,
            'file'              => $this->file,
            'file_url'          => asset_upload('file/block/' . $this->file),
        ];
    }
}
