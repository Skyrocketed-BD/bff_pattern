<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankNCashResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id_bank_n_cash" => $this->id_bank_n_cash,
            "id_coa"         => $this->id_coa,
            "coa_number"     => $this->toCoa->coa,
            "name"           => $this->toCoa->name,
            "type"           => $this->type,
            "show"           => $this->show
        ];
    }
}
