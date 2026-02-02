<?php

namespace App\Http\Resources\contract_legal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KasusNlResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_kasus_nl'   => $this->id_kasus_nl,
            'id_tahapan_nl' => $this->id_tahapan_nl ?? '-',
            'tahapan'       => $this->toTahapanNl->name ?? '-',
            'no'            => $this->no,
            'nama'          => $this->nama,
            'tanggal'       => $this->tanggal,
            'keterangan'    => $this->keterangan,
            'status'        => $this->status,
            'riwayat'      => $this->toKasusRiwayatNl->sortByDesc('id_kasus_riwayat_nl')->values()->map(function ($riwayat) {
                return [
                    'id_kasus_riwayat_nl' => $riwayat->id_kasus_riwayat_nl,
                    'tanggal'             => $riwayat->tanggal,
                    'tahapan'             => $riwayat->nama,
                    'tipe'                => $riwayat->toTahapanNl->name,
                    'keterangan'          => $riwayat->deskripsi,
                    'files'               => $riwayat->toUploadKasusRiwayatNl->map(function ($file) {
                        return [
                            'id_upload_kasus_riwayat_nl' => $file->id_upload_kasus_riwayat_nl,
                            'url'                        => asset_upload('file/kasus_riwayat_nl/' . $file->file),
                            'judul'                      => $file->judul
                        ];
                    })
                ];
            }),
        ];
    }
}
