<?php

namespace App\Models\operation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'operation';

    // untuk default tabel
    protected $table = 'block';

    // untuk default primary key
    protected $primaryKey = 'id_block';

    // untuk fillable
    protected $fillable = [
        'id_kontraktor',
        'name',
        'file',
        'created_by',
        'updated_by',
    ];

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
