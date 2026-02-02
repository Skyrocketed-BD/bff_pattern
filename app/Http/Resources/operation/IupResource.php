<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_iup_area'       => $this->id_iup_area,
            'name'              => $this->name,
            'file'              => $this->file,
            'file_url'          => asset_upload('file/area_iup/' . $this->file),
        ];
    }
}
