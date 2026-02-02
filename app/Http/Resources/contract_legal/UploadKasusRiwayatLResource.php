<?php

namespace App\Http\Resources\contract_legal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UploadKasusRiwayatLResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_upload_kasus_riwayat_l' => $this->id_upload_kasus_riwayat_l,
            'id_kasus_riwayat_l'        => $this->id_kasus_riwayat_l,
            'file'                      => $this->file,
            'judul'                     => $this->judul
        ];
    }
}
