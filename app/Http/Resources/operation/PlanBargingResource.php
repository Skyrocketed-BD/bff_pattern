<?php

namespace App\Http\Resources\operation;

use App\Http\Resources\finance\TransactionTermResource;
use App\Http\Resources\operation\ProvisionCoaResource;
use App\Models\operation\Cog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanBargingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cog = Cog::select('type')
            ->whereRaw('? BETWEEN CAST(min AS DECIMAL(10,2)) AND CAST(max AS DECIMAL(10,2))', [$this->ni]) // presisi where untuk float
            ->where('id_kontraktor', $this->id_kontraktor)
            ->orderBy('min', 'asc')
            ->first();

        $type  = strtolower(str_replace(' Grade', '', $cog->type ?? 'waste'));

        $simg = 0;
        if ($this->mgo2 > 0) {
            $simg = ($this->sio2 / $this->mgo2);
        }


        $response['id_plan_barging'] = $this->id_plan_barging;
        $response['pb_name']         = $this->pb_name;
        $response['number_si']       = $this->toShippingInstruction->number_si ?? null;
        $response['date']            = $this->date;
        $response['shipping_method'] = $this->shipping_method;
        $response['type']            = $type;
        $response['ni']              = floatval($this->ni);
        $response['fe']              = floatval($this->fe);
        $response['co']              = floatval($this->co);
        $response['sio2']            = floatval($this->sio2);
        $response['mgo2']            = floatval($this->mgo2);
        $response['simg']            = round($simg, 2);
        $response['ritasi']          = $this->ritasi;
        $response['mc']              = floatval($this->mc);
        $response['tonage']          = floatval($this->tonage);
        $response['attachment']      = asset_upload('file/plan_barging/' . $this->attachment);

        $response['id_shipping_instruction'] = $this->toShippingInstruction->id_shipping_instruction ?? null;
        $response['status']                  = $this->toShippingInstruction->status ?? null;
        $response['reject_reason']           = $this->toShippingInstruction->reject_reason ?? null;

        // invoice_fob
        $response['transaction_number'] = $this->toInvoiceFob->toTransaction->transaction_number ?? null;
        $response['id_transaction']     = $this->toInvoiceFob->toTransaction->id_transaction ?? null;
        
        $response['id_invoice_bill'] = $this->toInvoiceFob->toInvoiceBill->id_invoice_bill ?? null;
        $response['invoice_bill']    = $this->toInvoiceFob->transaction_number ?? null;

        $response['reference_number']   = $this->toInvoiceFob->reference_number ?? null;
        $response['hpm']                = $this->toInvoiceFob->hpm ?? null;
        $response['kurs']               = $this->toInvoiceFob->kurs ?? null;
        $response['ni_invoice_fob']     = $this->toInvoiceFob->ni ?? null;
        $response['price']              = $this->toInvoiceFob->price ?? null;
        $response['durasi_hari']        = empty($this->toInvoiceFob->date) ? null : count_days($this->toInvoiceFob->date, date('Y-m-d'));

        if ($this->toInvoiceFob) {
            $response['invoice_fob'] = InvoiceFobResource::make($this->toInvoiceFob);
        } else {
            $response['invoice_fob'] = [];
        }

        if ($this->toShippingInstruction) {
            $response['shipping_instruction'] = ShippingInstructionResource::make($this->toShippingInstruction);
        } else {
            $response['shipping_instruction'] = [];
        }

        if ($this->toPlanBargingDetail) {
            $response['details'] = PlanBargingDetailResource::collection($this->toPlanBargingDetail);
        } else {
            $response['details'] = [];
        }

        if (empty($this->toInvoiceFob->toInvoiceBill->toTransaction)) {
            $response['transaction_terms'] = [];
        } else {
            $response['transaction_terms'] = TransactionTermResource::collection($this->toInvoiceFob->toInvoiceBill->toTransaction->toTransactionTerm);
        }

        if (empty($this->toShippingInstruction->toProvision)) {
            $response['provision_coa'] = [];
        } else {
            $response['provision_coa'] = ProvisionCoaResource::collection($this->toShippingInstruction->toProvision->toProvisionCoa);
        }

        return $response;
    }
}
