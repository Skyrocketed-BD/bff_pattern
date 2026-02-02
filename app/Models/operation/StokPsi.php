<?php

namespace App\Models\operation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokPsi extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'stok_psis';

    // primary key
    protected $primaryKey = 'id_stok_psi';

    // fillable
    protected $fillable = [
        'id_kontraktor',
        'id_stok_eto',
        'id_stok_efo',
        'id_dom_eto',
        'id_dom_efo',
        'date',
        'type',
        'ni',
        'fe',
        'co',
        'sio2',
        'mgo2',
        'tonage',
        'mc',
        'ritasi',
        'attachment',
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

    // relasi ke tabel plan_barging_detail eto
    public function toPlanBargingDetailEto()
    {
        return $this->hasMany(PlanBargingDetail::class, 'id_stok_eto', 'id_stok_eto');
    }

    // relasi ke tabel plan_barging_detail efo
    public function toPlanBargingDetailEfo()
    {
        return $this->hasMany(PlanBargingDetail::class, 'id_stok_efo', 'id_stok_efo');
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
