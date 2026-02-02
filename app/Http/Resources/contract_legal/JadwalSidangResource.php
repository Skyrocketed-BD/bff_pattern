<?php

namespace App\Http\Resources\contract_legal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JadwalSidangResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response['id_jadwal_sidang']       = $this->id_jadwal_sidang;
        $response['no']                     = $this->no;
        $response['nama']                   = $this->nama;
        $response['tgl_waktu_sidang']       = $this->tgl_waktu_sidang;
        $response['keterangan']             = $this->keterangan;
        $response['status']                 = $this->status;

        if ($this->toUploadJadwalSidang->count() > 0) {
            $response['files'] = UploadJadwalSidangResource::collection($this->toUploadJadwalSidang)->map(function ($file) use ($request) {
                return $file->toArray($request) + ['url' => asset_upload('file/jadwal_sidang/' . $file->file)];
            });
        } else {
            $response['files'] = [];
        }

        return $response;
    }
}
