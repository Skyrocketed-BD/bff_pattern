<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoaHead extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'coa_head';

    protected $primaryKey = 'id_coa_head';

    protected $fillable = [
        'id_coa_group',
        'name',
        'coa',
        'created_by',
        'updated_by',
    ];

    // relasi
    protected $with = ['toCoaGroup'];

    // relasi ke tabel coa_group
    public function toCoaGroup()
    {
        return $this->belongsTo(CoaGroup::class, 'id_coa_group', 'id_coa_group');
    }

    // relasi ke tabel coa_body
    public function toCoaBody()
    {
        return $this->hasMany(CoaBody::class, 'id_coa_head', 'id_coa_head');
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
