<?php

namespace App\Models\contract_legal;

use App\Models\main\Kontak;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Kontrak extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'kontrak';

    protected $primaryKey = 'id_kontrak';

    protected $fillable = [
        'id_kontrak',
        'no_kontrak',
        'nama_perusahaan',
        'tgl_mulai',
        'tgl_akhir',
        'status',
        'attachment',
        'created_by',
        'updated_by'
    ];

    public function toLampiranKontrak()
    {
        return $this->hasMany(LampiranKontrak::class, 'id_kontrak', 'id_kontrak');
    }

    public function toKontrakTahapan()
    {
        return $this->hasMany(KontrakTahapan::class, 'id_kontrak', 'id_kontrak');
    }

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
