<?php

namespace App\Models\operation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slot extends Model
{
    use HasFactory;

    // spesific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'slots';

    // primary key
    protected $primaryKey = 'id_slot';

    // fillable
    protected $fillable = [
        'id_jetty',
        'name',
        'created_by',
        'updated_by',
    ];

    public function toJetty()
    {
        return $this->belongsTo(Jetty::class, 'id_jetty', 'id_jetty');
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
