<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportBodyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_report_body'  => $this->id_report_body,
            'id_report_title' => $this->id_report_title,
            'id_coa'          => $this->id_coa,
            'name'            => $this->toCoa->name ?? '-',
            'coa'             => $this->toCoa->coa ?? '-',
        ];
    }
}
