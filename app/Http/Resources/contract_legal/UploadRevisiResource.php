<?php

namespace App\Http\Resources\contract_legal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UploadRevisiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_upload_revisi'                    => $this->id_upload_revisi,
            'id_revisi'                           => $this->id_revisi,
            'id_upload_kontrak_tahapan'           => $this->id_upload_kontrak_tahapan,
            'id_upload'                           => $this->id_upload,
            'file'                                => $this->file
        ];
    }
}
