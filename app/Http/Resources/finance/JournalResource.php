<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_journal'     => $this->id_journal,
            'name'           => $this->name,
            'category'       => $this->category,
            'alocation'      => $this->alocation,
            'is_outstanding' => $this->is_outstanding
        ];
    }
}
