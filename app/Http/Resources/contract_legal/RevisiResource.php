<?php

namespace App\Http\Resources\contract_legal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RevisiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_revisi'              => $this->id_revisi,
            'revisi_ke'              => $this->revisi_ke,
            'keterangan'             => $this->keterangan,
            'files'                  => $this->toUploadRevisi->map(function ($file) {
                return [
                    'id_upload_revisi' => $file->id_upload_revisi,
                    'url'                         => asset_upload('file/upload_revisi/' . $file->file),
                    'judul'                       => $file->judul
                ];
            })
        ];
    }
}
