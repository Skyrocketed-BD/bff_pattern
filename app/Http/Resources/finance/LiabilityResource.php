<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiabilityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response['id_liability'] = $this->id_liability;
        $response['id_kontak']    = $this->id_kontak;
        $response['contact_name'] = $this->toKontak->name;
        $response['jenis']        = $this->toKontak->toKontakJenis->name;

        $advance_payment_detail = $this->toLiabilityDetail->where('status', 'valid')->toArray();

        $total = count($advance_payment_detail) > 0
            ? array_sum(array_map(function ($item) {
                return $item['category'] === 'penerimaan' ? $item['value'] : -$item['value'];
            }, $advance_payment_detail))
            : 0;

        $response['total'] = $total;

        if ($advance_payment_detail) $response['details'] = $advance_payment_detail;

        return $response;
    }
}
