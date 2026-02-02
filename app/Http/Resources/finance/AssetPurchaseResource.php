<?php

namespace App\Http\Resources\finance;

use App\Models\main\Kontak;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetPurchaseResource extends JsonResource
{
    /**
     * Create a new resource instance.
     *
     * @return void
     */
    public function __construct(
        mixed $resource,
        public readonly ?string $is_outstanding = null
    ) {
        parent::__construct($resource);
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $kontak = Kontak::find($this->id_kontak);

        $transaction['transaction_number'] = $this->transaction_number;
        $transaction['reference_number']   = $this->reference_number;
        $transaction['kontak']             = $kontak->name;
        $transaction['date']               = $this->date;
        $transaction['value']              = $this->value;
        $transaction['description']        = $this->description;
        if ($this->attachment !== null) {
            $transaction['attachment']     = $this->is_outstanding === '1' ? asset_upload('file/transaction/' . $this->attachment) : asset_upload('file/transaction_full/' . $this->attachment);
        }

        return $transaction;
    }
}
