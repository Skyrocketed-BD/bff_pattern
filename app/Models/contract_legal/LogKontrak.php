<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogKontrak extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'log_kontrak';

    protected $primaryKey = 'id_log_kontrak';

    protected $fillable = [
        'id_log_kontrak',
        'id_kontrak',
        'no_kontrak',
        'created_by',
        'updated_by'
    ];

    public function toKontrak()
    {
        return $this->belongsTo(Kontrak::class, 'id_kontrak', 'id_kontrak');
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
