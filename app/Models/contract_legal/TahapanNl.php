<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TahapanNl extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'tahapan_nl';

    protected $primaryKey = 'id_tahapan_nl';

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
