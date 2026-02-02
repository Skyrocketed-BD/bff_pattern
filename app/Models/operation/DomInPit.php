<?php

namespace App\Models\operation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomInPit extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'dom_in_pit';

    // primary key
    protected $primaryKey = 'id_dom_in_pit';

    // fillable
    protected $fillable = [
        'id_kontraktor',
        'id_pit',
        'name',
        'created_by',
        'updated_by'
    ];

    public function toPit()
    {
        return $this->belongsTo(Pit::class, 'id_pit', 'id_pit');
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
