<?php

namespace App\Http\Resources\operation;

use App\Http\Resources\finance\ReceiptResource;
use App\Http\Resources\finance\TransactionResource;
use App\Http\Resources\finance\TransactionTermResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceFobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response['id_invoice_fob']     = $this->id_invoice_fob;
        $response['id_plan_barging']    = $this->id_plan_barging;
        $response['id_kontak']          = $this->id_kontak;
        $response['id_journal']         = $this->id_journal;
        $response['id_invoice_bill']    = $this->toInvoiceBill->id_invoice_bill ?? null;

        $response['transaction_number'] = $this->transaction_number;
        $response['plan_barging']       = $this->toPlanBarging->pb_name;
        $response['journal']            = $this->toJournal->name;
        $response['kontraktor']         = $this->toPlanBarging->toKontraktor->company ?? null;
        $response['buyer_name']         = $this->toKontak->name ?? null;
        $response['date']               = $this->date;
        $response['hma']                = $this->hma;
        $response['hpm']                = $this->hpm;
        $response['kurs']               = $this->kurs;
        $response['ni']                 = $this->ni;
        $response['mc']                 = $this->mc;
        $response['tonage']             = $this->tonage;
        $response['price']              = $this->price;
        $response['description']        = $this->description;
        $response['reference_number']   = $this->reference_number;

        if ($this->toTransaction) {
            $response['transaction'] = TransactionResource::make($this->toTransaction);

            if ($this->toTransaction->toReceipts) {
                $response['receipts'] = ReceiptResource::collection($this->toTransaction->toReceipts);
            } else {
                $response['receipts'] = [];
            }

            if ($this->toTransaction->toTransactionTerm) {
                $response['transaction_terms'] = TransactionTermResource::collection($this->toTransaction->toTransactionTerm);
            } else {
                $response['transaction_terms'] = [];
            }
        } else {
            $response['transaction']       = null;
            $response['receipts']          = null;
            $response['transaction_terms'] = null;
        }

        return $response;
    }
}
