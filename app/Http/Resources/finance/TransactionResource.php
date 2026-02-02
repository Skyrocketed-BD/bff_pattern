<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response['id_transaction']     = $this->id_transaction;
        $response['id_kontak']          = $this->id_kontak;
        $response['id_journal']         = $this->id_journal;
        $response['id_invoice_bill']    = $this->id_invoice_bill;
        $response['category']           = $this->category;
        $response['transaction_number'] = $this->transaction_number;
        $response['reference_number']   = $this->reference_number;
        $response['invoice_bill']       = $this->toInvoiceBill->transaction_number ?? null;
        $response['journal']            = $this->toJournal->name;
        $response['date']               = $this->date;
        $response['from_or_to']         = $this->toKontak->name ?? '-';
        $response['description']        = $this->description;
        $response['in_ex']              = $this->in_ex;
        $response['status']             = $this->status;
        $response['value']              = $this->value;
        $response['category']           = $this->category;
        $response['attachment']         = asset_upload('file/transaction/' . $this->attachment);
        $response['durasi_hari']        = empty($this->date) ? null : count_days($this->date, date('Y-m-d'));

        if ($this->category == 'penerimaan' || $request->type == 'piutang') {
            $bayar = $this->toReceipts->where('status', 'valid')->sum('value');

            $response['bayar'] = $bayar;

            $sisa = ($this->value - $bayar);

            $response['sisa'] = $sisa;
        }

        if ($this->category == 'pengeluaran' || $request->type == 'utang') {
            $bayar = $this->toExpenditure->where('status', 'valid')->sum('value');

            $response['bayar'] = $bayar;

            $sisa = ($this->value - $bayar);

            $response['sisa'] = $sisa;
        }

        if ($this->toTransactionTerm) {
            $response['transaction_term'] = TransactionTermResource::collection($this->toTransactionTerm);
        } else {
            $response['transaction_term'] = [];
        }

        return $response;
    }
}
