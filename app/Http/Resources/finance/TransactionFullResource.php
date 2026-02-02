<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionFullResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_transaction_full' => $this->id_transaction_full,
            'id_journal'          => $this->id_journal,
            'id_kontak'           => $this->id_kontak,
            'id_transaction_bill' => $this->id_transaction_bill,
            'transaction_number'  => $this->transaction_number,
            'invoice_number'      => $this->invoice_number,
            'efaktur_number'      => $this->efaktur_number,
            'date'                => $this->date,
            'from_or_to'          => $this->toKontak->name,
            'description'         => $this->description,
            'attachment'          => asset_upload('file/transaction_full/' . $this->attachment),
            'category'            => $this->category,
            'record_type'         => $this->record_type,
            'value'               => $this->value,
            'in_ex'               => $this->in_ex,
            'status'              => $this->status
        ];
    }
}
