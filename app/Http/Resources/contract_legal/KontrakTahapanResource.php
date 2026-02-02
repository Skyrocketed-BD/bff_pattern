<?php

namespace App\Http\Resources\contract_legal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KontrakTahapanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id_kontrak_tahapan'         => $this->id_kontrak_tahapan,
            'id_tahapan_k'               => $this->id_tahapan_k,
            'no_kontrak'                 => $this->toKontrak->no_kontrak,
            'nama_perusahaan'            => $this->toKontrak->nama_perusahaan,
            'tahapan'                    => $this->toTahapanK->name,
            'tgl'                        => $this->tgl,
            'keterangan'                 => $this->keterangan,
            'status'                     => $this->status,
            'files'                      => $this->toUploadKontrakTahapan->map(function ($file) {
                $lastRow = $file->toUploadRevisi->last();

                return [
                    'id_upload_kontrak_tahapan'    => $file->id_upload_kontrak_tahapan,
                    'judul'                        => $file->judul,
                    'url'                          => $lastRow !== null ? asset_upload('file/upload_revisi/' . $lastRow->file) : asset_upload('file/upload_kontrak_tahapan/' . $file->file)
                ];
            }),
            'revisi'                     => $this->toRevisi->map(function ($revisi) {
                return [
                    'id_revisi'            => $revisi->id_revisi,
                    'revisi_ke'            => $revisi->revisi_ke,
                    'keterangan'           => $revisi->keterangan,
                    'files'                => $revisi->toUploadRevisi->map(function ($file) {
                        $originalUpload = $file->originalUpload;

                        return [
                            'id_upload_revisi'    => $file->id_upload_revisi,
                            'judul'               => $originalUpload ? $originalUpload->judul : null,
                            'url'                 => asset_upload('file/upload_revisi/' . $file->file)
                        ];
                    }),
                ];
            })
        ];
        return $data;
    }
}
