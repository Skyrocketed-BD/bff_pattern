<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TahapanK extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'tahapan_k';

    protected $primaryKey = 'id_tahapan_k';

    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
    ];

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
