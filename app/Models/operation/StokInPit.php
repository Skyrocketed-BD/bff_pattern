<?php

namespace App\Models\operation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StokInPit extends Model
{
    use HasFactory, SoftDeletes;

    // specific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'stok_in_pits';

    // primary key
    protected $primaryKey = 'id_stok_in_pit';

    // fillable
    protected $fillable = [
        'id_kontraktor',
        'id_block',
        'id_pit',
        'id_dom_in_pit',
        'sample_id',
        'date',
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

    public function toBlock()
    {
        return $this->belongsTo(Block::class, 'id_block', 'id_block');
    }

    public function toPit()
    {
        return $this->belongsTo(Pit::class, 'id_pit', 'id_pit');
    }

    public function toDomInPit()
    {
        return $this->belongsTo(DomInPit::class, 'id_dom_in_pit', 'id_dom_in_pit');
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
