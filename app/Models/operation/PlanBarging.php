<?php

namespace App\Models\operation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanBarging extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'plan_bargings';

    // primary key
    protected $primaryKey = 'id_plan_barging';

    // fillable
    protected $fillable = [
        'id_kontraktor',
        'pb_name',
        'date',
        'attachment',
        'shipping_method',
        'ni',
        'fe',
        'co',
        'sio2',
        'mgo2',
        'mc',
        'tonage',
        'ritasi',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'ni'     => 'float',
        'fe'     => 'float',
        'co'     => 'float',
        'sio2'   => 'float',
        'mgo2'   => 'float',
        'tonage' => 'float',
    ];

    public function toKontraktor()
    {
        return $this->belongsTo(Kontraktor::class, 'id_kontraktor', 'id_kontraktor');
    }

    public function toPlanBargingDetail()
    {
        return $this->hasMany(PlanBargingDetail::class, 'id_plan_barging', 'id_plan_barging');
    }

    public function toShippingInstruction()
    {
        return $this->belongsTo(ShippingInstruction::class, 'id_plan_barging', 'id_plan_barging');
    }

    public function toInvoiceFob()
    {
        return $this->belongsTo(InvoiceFob::class, 'id_plan_barging', 'id_plan_barging');
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
