<?php

namespace App\Models\operation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanBargingDetail extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'plan_barging_detail';

    // primary key
    protected $primaryKey = 'id_plan_barging_detail';

    protected $with = ['toDomEto', 'toDomEfo'];

    // fillable
    protected $fillable = [
        'id_plan_barging',
        'id_stok_eto',
        'id_stok_efo',
        'id_dom_eto',
        'id_dom_efo',
        'type',
        'ni',
        'fe',
        'co',
        'sio2',
        'mgo2',
        'mc',
        'tonage',
        'ritasi',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ni'     => 'float',
        'fe'     => 'float',
        'co'     => 'float',
        'sio2'   => 'float',
        'mgo2'   => 'float',
        'tonage' => 'float',
    ];

    public function toPlanBarging()
    {
        return $this->belongsTo(PlanBarging::class, 'id_plan_barging', 'id_plan_barging');
    }

    // relasi ke tabel stok eto
    public function toStokEto()
    {
        return $this->belongsTo(StokEto::class, 'id_stok_eto', 'id_stok_eto');
    }

    // relasi ke tabel stok efo
    public function toStokEfo()
    {
        return $this->belongsTo(StokEfo::class, 'id_stok_efo', 'id_stok_efo');
    }

    public function toDomEto()
    {
        return $this->belongsTo(DomEto::class, 'id_dom_eto', 'id_dom_eto');
    }

    public function toDomEfo()
    {
        return $this->belongsTo(DomEfo::class, 'id_dom_efo', 'id_dom_efo');
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
