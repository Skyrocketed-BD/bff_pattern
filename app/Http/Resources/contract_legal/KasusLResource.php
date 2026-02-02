<?php

namespace App\Http\Resources\contract_legal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KasusLResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestRiwayatNama = $this->toKasusRiwayatL->last()?->nama;

        return [
            'id_kasus_l'   => $this->id_kasus_l,
            'id_tahapan_l' => $this->id_tahapan_l ?? '-',
            'tahapan'      => $latestRiwayatNama ?? '-',
            'no'           => $this->no,
            'nama'         => $this->nama,
            'tanggal'      => $this->tanggal,
            'keterangan'   => $this->keterangan,
            'status'       => $this->status,
            'riwayat'      => $this->toKasusRiwayatL->sortByDesc('id_kasus_riwayat_l')->values()->map(function ($riwayat) {
                return [
                    'id_kasus_riwayat_l'  => $riwayat->id_kasus_riwayat_l,
                    'id_jadwal_sidang'    => $riwayat->toJadwalSidang ? $riwayat->toJadwalSidang->id_jadwal_sidang : null,
                    'tanggal'             => $riwayat->tanggal,
                    "nama"                => $riwayat->nama,
                    'tahapan'             => $riwayat->toTahapanL->name,
                    'kategori'            => $riwayat->toTahapanL->category,
                    'jadwal_sidang'       => $riwayat->toJadwalSidang ? $riwayat->toJadwalSidang->tgl_waktu_sidang : null,
                    'keterangan'          => $riwayat->deskripsi,
                    'status'              => $this->status,
                    'files'               => $riwayat->toUploadKasusRiwayatL->map(function ($file) {
                        return [
                            'id_upload_kasus_riwayat_l'  => $file->id_upload_kasus_riwayat_l,
                            'url'                        => asset_upload('file/kasus_riwayat_l/' . $file->file),
                            'judul'                      => $file->judul
                        ];
                    })
                ];
            }),
        ];
    }
}
