<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InfrastructureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_infrastructure' => $this->id_infrastructure,
            'id_kontraktor'     => $this->id_kontraktor,
            'company'           => $this->toKontraktor->company ?? 'IUP',
            'name'              => $this->name,
            'file'              => asset_upload('file/infrastructure/' . $this->file),
            'file_name'         => $this->file,
            'category'          => $this->category
        ];
    }
}
