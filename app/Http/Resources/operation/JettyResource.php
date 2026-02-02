<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JettyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_jetty'          => $this->id_jetty,
            'name'              => $this->name,
            'file'              => $this->file,
            'file_url'          => asset_upload('file/jetty/' . $this->file),
        ];
    }
}
