<?php

namespace App\Http\Resources\operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingInstructionApproveResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_shipping_instruction_approve' => $this->id_shipping_instruction_approve,
            'id_shipping_instruction'         => $this->id_shipping_instruction,
            'id_users'                        => $this->id_users,
            'user'                            => $this->toUser->name ?? '-',
            'id_role'                         => $this->toUser->id_role ?? '-',
            'role'                            => $this->toUser->toRole->name ?? '-',
            'date'                            => $this->date,
            'status'                          => $this->status,
            'reject_reason'                   => $this->reject_reason,
        ];
    }
}
