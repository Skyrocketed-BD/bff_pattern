<?php

namespace App\Models\operation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pit extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'pit';

    // primary key
    protected $primaryKey = 'id_pit';

    // fillable
    protected $fillable = [
        'id_kontraktor',
        'id_block',
        'name',
        'created_by',
        'updated_by'
    ];

    public function toBlock()
    {
        return $this->belongsTo(Block::class, 'id_block', 'id_block');
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
