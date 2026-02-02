<?php

namespace App\Http\Resources\operation;

use App\Http\Resources\finance\TransactionTermResource;
use App\Models\main\Kontak;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvisionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $plan_barging_detail = $this->toShippingInstruction->toPlanBarging->toPlanBargingDetail;

        $ni     = [];
        $fe     = [];
        $co     = [];
        $sio2   = [];
        $mgo2   = [];
        $simg   = [];
        $mc     = [];
        $tonage = [];
        foreach ($plan_barging_detail as $key => $value) {
            $ni[]     = $value->ni;
            $fe[]     = $value->fe;
            $co[]     = $value->co;
            $sio2[]   = $value->sio2;
            $mgo2[]   = $value->mgo2;
            $simg[]   = ($value->sio2 / $value->mgo2);
            $mc[]     = $value->mc;
            $tonage[] = $value->tonage;
        }

        $count_tonage = array_sum($tonage);
        $count_ni     = (sumProductArray($ni, $tonage) == 0 || $count_tonage == 0) ? 0 : round((sumProductArray($ni, $tonage) / $count_tonage), 2);
        $count_fe     = (sumProductArray($fe, $tonage) == 0 || $count_tonage == 0) ? 0 : round((sumProductArray($fe, $tonage) / $count_tonage), 2);
        $count_co     = (sumProductArray($co, $tonage) == 0 || $count_tonage == 0) ? 0 : round((sumProductArray($co, $tonage) / $count_tonage), 2);
        $count_sio2   = (sumProductArray($sio2, $tonage) == 0 || $count_tonage == 0) ? 0 : round((sumProductArray($sio2, $tonage) / $count_tonage), 2);
        $count_mgo2   = (sumProductArray($mgo2, $tonage) == 0 || $count_tonage == 0) ? 0 : round((sumProductArray($mgo2, $tonage) / $count_tonage), 2);
        $count_simg   = (sumProductArray($simg, $tonage) == 0 || $count_tonage == 0) ? 0 : round((sumProductArray($simg, $tonage) / $count_tonage), 2);
        $count_mc     = (sumProductArray($mc, $tonage) == 0 || $count_tonage == 0) ? 0 : round((sumProductArray($mc, $tonage) / $count_tonage), 2);

        $response['id_provision']            = $this->id_provision;
        $response['id_plan_barging']         = $this->toShippingInstruction->id_plan_barging;
        $response['id_shipping_instruction'] = $this->id_shipping_instruction;
        $response['kontraktor']              = $this->toShippingInstruction->toKontraktor->company;
        $response['pb_name']                 = $this->toShippingInstruction->toPlanBarging->pb_name ?? null;
        $response['number_si']               = $this->toShippingInstruction->number_si;
        $response['inv_provision']           = $this->inv_provision;
        $response['method_sales']            = $this->method_sales;
        $response['departure_date']          = $this->departure_date;
        $response['pnbp_provision']          = $this->pnbp_provision;
        $response['selling_price']           = $this->selling_price;
        $response['tonage_actual']           = $this->tonage_actual;
        $response['attachment_dsr']          = $this->attachment ? asset_upload('file/provision/' . $this->attachment) : null;
        $response['attachment_pnbp_prov']    = $this->attachment_pnbp_prov ? asset_upload('file/provision/' . $this->attachment_pnbp_prov) : null;
        // akan dihapus
        $response['attachment'] = $this->attachment ? asset_upload('file/provision/' . $this->attachment) : null;

        $response['barge_name']         = $this->toShippingInstruction->tug_boat . ' / ' . $this->toShippingInstruction->barge;
        $response['sales_goals']        = $this->toShippingInstruction->consignee;
        $response['loading_port']       = $this->toShippingInstruction->loading_port;
        $response['load_amount']        = $this->toShippingInstruction->load_amount;
        $response['load_date_start']    = $this->toShippingInstruction->load_date_start;
        $response['gross_tonage']       = $this->toShippingInstruction->gross_tonage;
        $response['ni']                 = $count_ni;
        $response['fe']                 = $count_fe;
        $response['mc']                 = $count_mc;
        $response['co']                 = $count_co;
        $response['sio2']               = $count_sio2;
        $response['mgo2']               = $count_mgo2;
        $response['simg']               = $count_simg;
        $response['tonage']             = $count_tonage;
        $response['prov_selling_price'] = ($this->toShippingInstruction->load_amount * $this->selling_price);

        // invoice_fob
        $response['id_kontak']       = $this->toShippingInstruction->toPlanBarging->toInvoiceFob->id_kontak;
        $response['id_journal']      = $this->toShippingInstruction->toPlanBarging->toInvoiceFob->id_journal ?? null;

        if ($this->method_sales == 'cif') {
            $response['pnbp_provision_final'] = $this->toProvisionCoa[0]->pay_pnbp ?? 0;
        } else {
            $response['pnbp_provision_final'] = $this->toProvisionCoa[1]->pay_pnbp ?? 0;
        }

        if (empty($this->toShippingInstruction->toPlanBarging->toInvoiceFob->toInvoiceBill->toTransaction->transaction_number)) {
            $response['transaction_number'] = $this->toShippingInstruction->toPlanBarging->toInvoiceFob->transaction_number ?? null; // kolom lama
        } else {
            $response['transaction_number'] = $this->toShippingInstruction->toPlanBarging->toInvoiceFob->toInvoiceBill->toTransaction->transaction_number ?? null; // kolom baru
        }

        $response['reference_number']   = $this->toShippingInstruction->toPlanBarging->toInvoiceFob->reference_number ?? null;
        $response['buyer_name']         = Kontak::find($this->toShippingInstruction->toPlanBarging->toInvoiceFob->id_kontak)->name ?? null;
        $response['hma']                = empty($this->toShippingInstruction->toPlanBarging->toInvoiceFob->hma) ? null : floatval($this->toShippingInstruction->toPlanBarging->toInvoiceFob->hma);
        $response['kurs']               = empty($this->toShippingInstruction->toPlanBarging->toInvoiceFob->kurs) ? null : floatval($this->toShippingInstruction->toPlanBarging->toInvoiceFob->kurs);
        $response['ni_fob']             = empty($this->toShippingInstruction->toPlanBarging->toInvoiceFob->ni) ? null : floatval($this->toShippingInstruction->toPlanBarging->toInvoiceFob->ni);
        $response['mc_fob']             = empty($this->toShippingInstruction->toPlanBarging->toInvoiceFob->mc) ? null : floatval($this->toShippingInstruction->toPlanBarging->toInvoiceFob->mc);
        $response['tonage_fob']         = empty($this->toShippingInstruction->toPlanBarging->toInvoiceFob->tonage) ? null : floatval($this->toShippingInstruction->toPlanBarging->toInvoiceFob->tonage);
        $response['total_fob']          = $this->toShippingInstruction->toPlanBarging->toInvoiceFob->price ?? null;
        $response['price']              = $this->toShippingInstruction->toPlanBarging->toInvoiceFob->toTransaction->value ?? null;
        $response['hpm']                = $this->toShippingInstruction->toPlanBarging->toInvoiceFob->hpm ?? null;
        $response['kurs']               = $this->toShippingInstruction->toPlanBarging->toInvoiceFob->kurs ?? null;

        if (empty($this->toShippingInstruction->toPlanBarging->toInvoiceFob->toTransaction->toTransactionTerm)) {
            if (empty($this->toShippingInstruction->toPlanBarging->toInvoiceFob->toInvoiceBill->toTransaction->toTransactionTerm)) {
                $response['transaction_terms'] = [];
            } else {
                $response['transaction_terms'] = TransactionTermResource::collection($this->toShippingInstruction->toPlanBarging->toInvoiceFob->toInvoiceBill->toTransaction->toTransactionTerm);
            }
        } else {
            $response['transaction_terms'] = TransactionTermResource::collection($this->toShippingInstruction->toPlanBarging->toInvoiceFob->toTransaction->toTransactionTerm);
        }

        if (empty($this->toShippingInstruction->toPlanBarging->toInvoiceFob->toTransaction)) {
            $total   = 0;
            $receipt = 0;
            $sisa    = $total - $receipt;
        } else {
            $receipts = $this->toShippingInstruction->toPlanBarging->toInvoiceFob->toTransaction->toReceipts;

            $receipt = [];
            foreach ($receipts as $key => $value) {
                if ($value->status == 'valid') {
                    $receipt[] = $value->value;
                }
            }

            $total   = $this->toShippingInstruction->toPlanBarging->toInvoiceFob->toTransaction->value;
            $receipt = array_sum($receipt);
            $sisa    = $total - $receipt;
        }

        // transaction
        $response['id_invoice_bill'] = $this->toShippingInstruction->toPlanBarging->toInvoiceFob->toInvoiceBill->id_invoice_bill ?? null;
        $response['id_transaction']  = $this->toShippingInstruction->toPlanBarging->toInvoiceFob->toTransaction->id_transaction ?? null;
        
        $response['total']           = $total;
        $response['receipt']         = $receipt;
        $response['sisa']            = $sisa;
        $response['provision_coa']   = ProvisionCoaResource::collection($this->toProvisionCoa)->toArray($request);

        return $response;
    }
}
