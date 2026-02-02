<?php

namespace App\Http\Resources\operation;

use App\Models\operation\Cog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StokEfoResource extends JsonResource
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
            ->whereRaw('? BETWEEN CAST(min AS DECIMAL(10,2)) AND CAST(max AS DECIMAL(10,2))', [$this->ni])  // presisi where untuk float
            ->where('id_kontraktor', $this->id_kontraktor)
            ->orderBy('min', 'asc')
            ->first();

        $type = strtolower(str_replace(' Grade', '', $cog->type ?? 'waste'));

        return [
            'id_stok_efo'           => $this->id_stok_efo,
            'id_dom_efo'            => $this->id_dom_efo,
            'dom_efo'               => $this->toDomEfo->name,
            'date_in'               => $this->date_in,
            'date_out'              => $this->date_out,
            'tonage_after'          => floatval($this->tonage_after),
            'mining_recovery_type'  => $this->mining_recovery_type,
            'mining_recovery_value' => floatval($this->mining_recovery_value),
            'type'                  => $type,
            'ni'                    => floatval($this->ni),
            'fe'                    => floatval($this->fe),
            'co'                    => floatval($this->co),
            'sio2'                  => floatval($this->sio2),
            'mgo2'                  => floatval($this->mgo2),
            'tonage'                => floatval($this->tonage),
            'ritasi'                => $this->ritasi,
            'simg'                  => round($simg, 2),
        ];
    }
}
