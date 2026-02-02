<?php

namespace App\Http\Resources\contract_legal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KasusRiwayatNlResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $response['id_kasus_riwayat_nl'] = $this->id_kasus_riwayat_nl;
        $response['id_kasus_nl']         = $this->id_kasus_nl;
        $response['kasus']               = $this->toKasusNl->no . ' - ' . $this->toKasusNl->nama;
        $response['id_tahapan_nl']       = $this->id_tahapan_nl;
        $response['tahapan']             = $this->toTahapanNl->name;
        $response['nama']                = $this->nama;
        $response['tanggal']             = $this->tanggal;
        $response['deskripsi']           = $this->deskripsi;

        if ($this->toUploadKasusRiwayatNl->count() > 0) {
            $response['files'] = UploadKasusRiwayatNlResource::collection($this->toUploadKasusRiwayatNl);
        } else {
            $response['files'] = [];
        }

        return $response;
    }
}
