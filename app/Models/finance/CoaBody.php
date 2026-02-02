<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoaBody extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'coa_body';

    protected $primaryKey = 'id_coa_body';

    protected $fillable = [
        'id_coa_head',
        'id_coa_clasification',
        'name',
        'coa',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id_coa_body'          => 'integer',
            'id_coa_head'          => 'integer',
            'id_coa_clasification' => 'integer',
        ];
    }

    // relasi
    protected $with = ['toCoaHead'];

    // relasi ke tabel coa_head
    public function toCoaHead()
    {
        return $this->belongsTo(CoaHead::class, 'id_coa_head', 'id_coa_head');
    }

    // relasi ke tabel coa_clasification
    public function toCoaClasification()
    {
        return $this->belongsTo(CoaClasification::class, 'id_coa_clasification', 'id_coa_clasification');
    }

    // relasi ke tabel coa
    public function toCoa()
    {
        return $this->hasMany(Coa::class, 'id_coa_body', 'id_coa_body');
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
