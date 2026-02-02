<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalSetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_journal_set' => $this->id_journal_set,
            'id_tax_rate'    => $this->id_tax_rate ?? null,
            'id_journal'     => $this->id_journal,
            'id_coa'         => $this->id_coa,
            'id_tax'         => $this->toTaxRate->id_tax ?? null,
            'name'           => $this->toCoa->name,
            'type'           => $this->type,
            'coa'            => $this->toCoa->coa,
            'open_input'     => $this->open_input,
            'is_beban'       => $this->toCoa->toCoaBody->toCoaClasification->group === 'beban' ? true : false,
            'is_tax'         => isset($this->toTaxCoa->id_tax) ? true : false,
        ];
    }
}
