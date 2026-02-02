<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DownPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response['id_down_payment']    = $this->id_down_payment;
        $response['id_kontak']          = $this->id_kontak;
        $response['contact_name']       = $this->toKontak->name;
        $response['jenis']              = $this->toKontak->toKontakJenis->name;

        $down_payment_detail = $this->toDownPaymentDetail->where('status', 'valid')->toArray();

        $total = count($down_payment_detail) > 0
            ? array_sum(array_map(function ($item) {
                return $item['category'] === 'penerimaan' ? $item['value'] : -$item['value'];
            }, $down_payment_detail))
            : 0;

        $response['total'] = $total;

        if ($down_payment_detail) $response['details'] = $down_payment_detail;

        return $response;
    }
}
