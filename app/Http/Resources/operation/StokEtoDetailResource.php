<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StokEtoDetailResource extends JsonResource
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
            'id_stok_eto_detail' => $this->id_stok_eto_detail,
            'id_stok_eto'        => $this->id_stok_eto,
            'id_dom_in_pit'      => $this->id_dom_in_pit,
            'dom_pit'            => $this->toDomInPit->name,
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
