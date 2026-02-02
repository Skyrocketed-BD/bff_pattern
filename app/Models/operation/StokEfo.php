<?php

namespace App\Models\operation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StokEfo extends Model
{
    use HasFactory, SoftDeletes;

    // specific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'stok_efos';

    // primary key
    protected $primaryKey = 'id_stok_efo';

    // fillable
    protected $fillable = [
        'id_kontraktor',
        'id_dom_efo',
        'date_in',
        'date_out',
        'tonage_after',
        'mining_recovery_type',
        'mining_recovery_value',
        'attachment',
        'ni',
        'fe',
        'co',
        'sio2',
        'mgo2',
        'tonage',
        'ritasi',
        'created_by',
        'updated_by'
    ];

    public function toStokEfoDetail()
    {
        return $this->hasMany(StokEfoDetail::class, 'id_stok_efo', 'id_stok_efo');
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
