<?php

namespace App\Models\operation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingInstruction extends Model
{
    use HasFactory;

    // spesific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'shipping_instructions';

    // primary key
    protected $primaryKey = 'id_shipping_instruction';

    // fillable
    protected $fillable = [
        'id_plan_barging',
        'id_kontraktor',
        'id_slot',
        'number_si',
        'consignee',
        'surveyor',
        'notify_party',
        'tug_boat',
        'barge',
        'gross_tonage',
        'loading_port',
        'unloading_port',
        'load_date_start',
        'load_date_finish',
        'load_amount',
        'information',
        'mining_inspector',
        'status',
        'reject_reason',
        'attachment',
        'created_by',
        'updated_by'
    ];

    public function toKontraktor()
    {
        return $this->belongsTo(Kontraktor::class, 'id_kontraktor', 'id_kontraktor');
    }

    public function toSlot()
    {
        return $this->belongsTo(Slot::class, 'id_slot', 'id_slot');
    }

    public function toPlanBarging() {
        return $this->belongsTo(PlanBarging::class, 'id_plan_barging', 'id_plan_barging');
    }

    public function toProvision() {
        return $this->belongsTo(Provision::class, 'id_shipping_instruction', 'id_shipping_instruction');
    }

    public function toShippingInstructionApprove() {
        return $this->hasMany(ShippingInstructionApprove::class, 'id_shipping_instruction', 'id_shipping_instruction');
    }

    public function scopeWhereKontraktor($query, string $id_kontraktor)
    {
        return $query->where('id_kontraktor', $id_kontraktor);
    }

    protected static function booted()
    {
        static::creating(function ($row) {
            $row->created_by = auth('api')->user()->id_users;
        });

        static::updating(function ($row) {
            $row->updated_by = auth('api')->user()->id_users;
        });
    }
}
