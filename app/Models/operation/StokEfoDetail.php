<?php

namespace App\Models\operation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StokEfoDetail extends Model
{
    use HasFactory, SoftDeletes;

    // specific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'stok_efo_detail';

    // primary key
    protected $primaryKey = 'id_stok_efo_detail';

    // fillable
    protected $fillable = [
        'id_stok_efo',
        'id_stok_eto',
        'id_dom_eto',
        'id_stok_in_pit',
        'id_dom_in_pit',
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
        'ni'     => 'float',
        'fe'     => 'float',
        'co'     => 'float',
        'sio2'   => 'float',
        'mgo2'   => 'float',
        'tonage' => 'float',
    ];

    public function toStokEfo()
    {
        return $this->belongsTo(StokEfo::class, 'id_stok_efo', 'id_stok_efo');
    }

    public function toDomEto()
    {
        return $this->belongsTo(DomEto::class, 'id_dom_eto', 'id_dom_eto');
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
