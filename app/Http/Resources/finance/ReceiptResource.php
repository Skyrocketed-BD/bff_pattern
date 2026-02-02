<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class ReceiptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_receipt'         => $this->id_receipt,
            'journal'            => $this->toJournal->name,
            'reference_number'   => $this->reference_number,
            'transaction_number' => $this->transaction_number,
            'date'               => $this->date,
            'receive_from'       => $this->toKontak->name,
            'pay_type'           => Config::get('constants.pay_type')[$this->pay_type],
            'value'              => $this->value,
            'in_ex'              => $this->in_ex,
            'description'        => $this->description,
            'attachment'         => asset_upload('file/receipt/' . $this->attachment),
            'status'             => $this->status,
        ];
    }
}
