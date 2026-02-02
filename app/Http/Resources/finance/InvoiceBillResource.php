<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceBillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_invoice_bill'     => $this->id_invoice_bill,
            'id_kontak'           => $this->id_kontak,
            'id_journal'          => $this->id_journal,
            'kontak_name'         => $this->toKontak->name,
            'transaction_number'  => $this->transaction_number,
            'reference_number'    => $this->reference_number,
            'inv_date'            => $this->inv_date,
            'due_date'            => $this->due_date,
            'total'               => $this->total,
            'description'         => $this->description,
            'category'            => $this->category,
            'type'                => $this->type,
            'in_ex'               => $this->in_ex,
            'payment_status'      => $this->payment_status,
            'is_outstanding'      => $this->is_outstanding,
            'is_actual'           => $this->is_actual,
            'payment_time_status' => $this->payment_time_status
        ];
    }
}
