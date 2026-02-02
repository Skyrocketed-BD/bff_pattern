<?php

namespace App\Models\main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KontakJenis extends Model
{
    use HasFactory;

    protected $table = 'kontak_jenis';

    protected $primaryKey = 'id_kontak_jenis';

    protected $fillable = [
        'name',
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
