<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoaGroup extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'coa_group';

    protected $primaryKey = 'id_coa_group';

    protected $fillable = [
        'name',
        'coa',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel coa_head
    public function toCoaHead()
    {
        return $this->hasMany(CoaHead::class, 'id_coa_group', 'id_coa_group');
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
