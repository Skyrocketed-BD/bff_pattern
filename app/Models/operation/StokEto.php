<?php

namespace App\Models\operation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StokEto extends Model
{
    use HasFactory, SoftDeletes;

    // specific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'stok_etos';

    // primary key
    protected $primaryKey = 'id_stok_eto';

    // fillable
    protected $fillable = [
        'id_kontraktor',
        'id_dom_eto',
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
        'updated_by',
    ];

    protected $casts = [
        'tonage_after'          => 'float',
        'mining_recovery_value' => 'float',
        'ni'                    => 'float',
        'fe'                    => 'float',
        'co'                    => 'float',
        'sio2'                  => 'float',
        'mgo2'                  => 'float',
        'tonage'                => 'float',
    ];

    public function toStokEtoDetail()
    {
        return $this->hasMany(StokEtoDetail::class, 'id_stok_eto', 'id_stok_eto');
    }

    public function toDomEto()
    {
        return $this->belongsTo(DomEto::class, 'id_dom_eto', 'id_dom_eto');
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
