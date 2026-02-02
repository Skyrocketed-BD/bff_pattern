<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StokEfoDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $simg = ($this->sio2 / $this->mgo2);

        return [
            'id_stok_efo_detail' => $this->id_stok_efo_detail,
            'id_stok_efo'        => $this->id_stok_efo,
            'id_dom_eto'         => $this->id_dom_eto,
            'dom_eto'            => $this->toDomEto->name,
            'ni'                 => floatval($this->ni),
            'fe'                 => floatval($this->fe),
            'co'                 => floatval($this->co),
            'sio2'               => floatval($this->sio2),
            'mgo2'               => floatval($this->mgo2),
            'simg'               => round($simg, 2),
            'tonage'             => floatval($this->tonage),
            'ritasi'             => $this->ritasi
        ];
    }
}
