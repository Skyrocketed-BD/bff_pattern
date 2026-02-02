<?php

namespace App\Http\Resources\main;

use App\Models\main\Kontak;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KontakResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response['id_kontak']  = $this->id_kontak;
        $response['id_kontrak'] = $this->id_kontrak;

        if ($this->is_company === 0 && $this->id_perusahaan !== null) {
            $response['company_name'] = Kontak::find($this->id_perusahaan);
        } else {
            $response['company_name'] = '';
        }
        $response['id_perusahaan']   = $this->id_perusahaan;
        $response['id_kontak_jenis'] = $this->id_kontak_jenis;
        $response['name']            = $this->name;
        $response['npwp']            = $this->npwp;
        $response['phone']           = $this->phone;
        $response['email']           = $this->email;
        $response['website']         = $this->website;
        $response['address']         = $this->address;
        $response['postal_code']     = $this->postal_code;
        $response['is_company']      = $this->is_company;
        $response['jenis']           = $this->toKontakJenis->name;
        $response['no_kontrak']      = $this->toKontrak->no_kontrak ?? null;
        $response['file_kontrak']    = empty($this->toKontrak->attachment) ? null :  asset_upload('file/kontrak/' . $this->toKontrak->attachment );

        return $response;
    }
}
