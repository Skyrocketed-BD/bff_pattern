<?php

namespace App\Http\Resources\contract_legal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UploadKasusRiwayatNlResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_upload_kasus_riwayat_nl' => $this->id_upload_kasus_riwayat_nl,
            'id_kasus_riwayat_nl'        => $this->id_kasus_riwayat_nl,
            'file'                       => $this->file,
            'judul'                      => $this->judul
        ];
    }
}
