<?php

namespace App\Http\Resources\contract_legal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UploadJadwalSidangResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_upload_jadwal_sidang' => $this->id_upload_jadwal_sidang,
            'id_jadwal_sidang'        => $this->id_jadwal_sidang,
            'judul'                   => $this->judul,
            'file'                    => $this->file
        ];
    }
}
