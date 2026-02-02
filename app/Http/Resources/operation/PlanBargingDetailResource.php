<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanBargingDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->type === 'eto') {
            $name_dom = $this->toDomEto->name;
        }

        if ($this->type === 'efo') {
            $name_dom = $this->toDomEfo->name;
        }

        $simg = 0;
        if ($this->mgo2 > 0) {
            $simg = ($this->sio2 / $this->mgo2);
        }

        return [
            'id_plan_barging_detail' => $this->id_plan_barging_detail,
            'id_plan_barging'        => $this->id_plan_barging,
            'id_stok_eto'            => $this->id_stok_eto,
            'id_stok_efo'            => $this->id_stok_efo,
            'id_dom_eto'             => $this->id_dom_eto,
            'id_dom_efo'             => $this->id_dom_efo,
            'name'                   => $name_dom,
            'ni'                     => $this->ni,
            'fe'                     => $this->fe,
            'co'                     => $this->co,
            'sio2'                   => $this->sio2,
            'mgo2'                   => $this->mgo2,
            'simg'                   => round($simg, 2),
            'tonage'                 => $this->tonage,
            'mc'                     => $this->mc,
            'ritasi'                 => $this->ritasi,
        ];
    }
}
