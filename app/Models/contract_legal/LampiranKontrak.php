<?php

namespace App\Models\contract_legal;

use App\Models\main\Kontak;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class LampiranKontrak extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'lampiran_kontrak';

    protected $primaryKey = 'id_lampiran_kontrak';

    protected $fillable = [
        'id_kontrak',
        'judul',
        'file',
        'created_by',
        'updated_by'
    ];

    public function toKontak()
    {
        return $this->setConnection('mysql')->belongsTo(Kontak::class, 'id_kontrak', 'id_kontrak');
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
