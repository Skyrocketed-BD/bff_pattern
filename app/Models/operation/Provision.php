<?php

namespace App\Models\operation;

use App\Models\main\Kontak;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provision extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'provision';

    // primary key
    protected $primaryKey = 'id_provision';

    // fillable
    protected $fillable = [
        'id_kontak',
        'id_shipping_instruction',
        'inv_provision',
        'method_sales',
        'departure_date',
        'pnbp_provision',
        'selling_price',
        'tonage_actual',
        'attachment',
        'attachment_pnbp_prov',
        'created_by',
        'updated_by',
    ];

    public function toShippingInstruction()
    {
        return $this->belongsTo(ShippingInstruction::class, 'id_shipping_instruction', 'id_shipping_instruction');
    }

    public function toProvisionCoa()
    {
        return $this->hasMany(ProvisionCoa::class, 'id_provision', 'id_provision');
    }

    public function toKontak()
    {
        return $this->setConnection('mysql')->belongsTo(Kontak::class, 'id_kontak', 'id_kontak');
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
