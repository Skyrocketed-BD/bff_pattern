<?php

namespace App\Http\Resources\operation;

use App\Models\operation\Cog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StokInPitResource extends JsonResource
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
            $simg = $this->sio2 / $this->mgo2;
        }

        $cog = Cog::select('type')
            ->whereRaw('? BETWEEN CAST(min AS DECIMAL(10,2)) AND CAST(max AS DECIMAL(10,2))', [$this->ni])  // presisi where untuk float
            ->where('id_kontraktor', $this->id_kontraktor)
            ->orderBy('min', 'asc')
            ->first();

        $type = strtolower(str_replace(' Grade', '', $cog->type ?? 'waste'));

        return [
            'id_stok_in_pit' => $this->id_stok_in_pit,
            'id_block'       => $this->id_block,
            'block'          => $this->toBlock->name,
            'id_pit'         => $this->id_pit,
            'pit'            => $this->toPit->name,
            'id_dom_in_pit'  => $this->id_dom_in_pit,
            'dome'           => $this->toDomInPit->name,
            'sample_id'      => $this->sample_id,
            'date'           => $this->date,
            'type'           => $type,
            'ni'             => floatval($this->ni),
            'sio2'           => floatval($this->sio2),
            'fe'             => floatval($this->fe),
            'co'             => floatval($this->co),
            'mgo2'           => floatval($this->mgo2),
            'tonage'         => floatval($this->tonage),
            'ritasi'         => $this->ritasi,
            'simg'           => $simg,
            'simg_round'     => round($simg, 2),
        ];
    }
}
