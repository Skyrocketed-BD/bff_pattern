<?php

namespace App\Http\Resources\contract_legal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KasusRiwayatLResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $response['id_kasus_riwayat_l'] = $this->id_kasus_riwayat_l;
        $response['id_kasus_l']         = $this->id_kasus_l;
        $response['kasus']              = $this->toKasusL->no . ' - ' . $this->toKasusL->nama;
        $response['id_tahapan_l']       = $this->id_tahapan_l;
        $response['tahapan']            = $this->toTahapanL->name;
        $response['nama']               = $this->nama;
        $response['tanggal']            = $this->tanggal;
        $response['jadwal_sidang']      = $this->toJadwalSidang->tgl_waktu_sidang;
        $response['deskripsi']          = $this->deskripsi;
        $response['tgl_sidang']         = $this->toJadwalSidang->tgl_sidang;
        $response['waktu_sidang']       = $this->toJadwalSidang->waktu_sidang;

        if ($this->toUploadKasusRiwayatL->count() > 0) {
            $response['files'] = UploadKasusRiwayatLResource::collection($this->toUploadKasusRiwayatL);
        } else {
            $response['files'] = [];
        }

        return $response;
    }
}
