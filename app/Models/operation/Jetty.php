<?php

namespace App\Models\operation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jetty extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'operation';

    // defined table name
    protected $table = 'jetty';

    // primary key
    protected $primaryKey = 'id_jetty';

    // fillable
    protected $fillable = [
        'name',
        'file',
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
