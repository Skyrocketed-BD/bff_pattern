<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_journal_entry'   => $this->id_journal_entry,
            'transaction_number' => $this->transaction_number,
            'date'               => $this->date,
            'value'              => $this->value,
            'description'        => $this->description,
            'status'             => $this->status,
            'attachment'         => $this->attachment ? asset_upload('file/journal_entries/' . $this->attachment) : null,
            'created_by'         => $this->created_by,
            'updated_by'         => $this->updated_by,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
