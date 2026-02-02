<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TahapanL extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'tahapan_l';

    protected $primaryKey = 'id_tahapan_l';

    protected $fillable = [
        'name',
        'category',
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
