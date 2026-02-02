<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportTitleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $result['id_report_title'] = $this->id_report_title;
        $result['id_report_menu']  = $this->id_report_menu;
        $result['name']            = $this->name;

        if ($this->toReportBody->count() > 0) {
            $result['report_body'] = ReportBodyResource::collection($this->toReportBody);
        }

        return $result;
    }
}
