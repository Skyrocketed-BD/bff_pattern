<?php

namespace App\Http\Resources\operation;

use App\Models\operation\Cog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StokPsiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $simg = 0;
        if ($this->mgo2 > 0) {
            $simg = ($this->sio2 / $this->mgo2);
        }

        $cog = Cog::select('type')
            ->whereRaw('? BETWEEN CAST(min AS DECIMAL(10,2)) AND CAST(max AS DECIMAL(10,2))', [$this->ni]) // presisi where untuk float
            ->where('id_kontraktor', $this->id_kontraktor)
            ->orderBy('min', 'asc')
            ->first();

        $type  = strtolower(str_replace(' Grade', '', $cog->type ?? 'waste'));

        if ($this->type === 'eto') {
            $name_dom    = $this->toDomEto->name;
            $tonage_sisa = ($this->tonage - $this->toPlanBargingDetailEto->sum('tonage'));
        }

        if ($this->type === 'efo') {
            $name_dom    = $this->toDomEfo->name;
            $tonage_sisa = ($this->tonage - $this->toPlanBargingDetailEfo->sum('tonage'));
        }

        return [
            'id_stok_psi' => $this->id_stok_psi,
            'id_stok_eto' => $this->id_stok_eto,
            'id_stok_efo' => $this->id_stok_efo,
            'name'        => $name_dom,
            'date'        => $this->date,
            'type'        => $type,
            'ni'          => $this->ni,
            'fe'          => $this->fe,
            'co'          => $this->co,
            'sio2'        => $this->sio2,
            'mgo2'        => $this->mgo2,
            'tonage'      => $tonage_sisa,
            'ritasi'      => $this->ritasi,
            'mc'          => $this->mc,
            'simg'        => round($simg, 2),
        ];
    }
}
