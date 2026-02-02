<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KontrakTahapan extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'kontrak_tahapan';

    protected $primaryKey = 'id_kontrak_tahapan';

    protected $fillable = [
        'id_tahapan_k',
        'id_kontrak',
        'tahapan',
        'tgl',
        'keterangan',
        'status',
        'created_by',
        'updated_by'
    ];

    public function toUploadKontrakTahapan()
    {
        return $this->hasMany(UploadKontrakTahapan::class, 'id_kontrak_tahapan', 'id_kontrak_tahapan');
    }

    public function toTahapanK()
    {
        return $this->belongsTo(TahapanK::class, 'id_tahapan_k', 'id_tahapan_k');
    }

    public function toKontrak()
    {
        return $this->belongsTo(Kontrak::class, 'id_kontrak', 'id_kontrak');
    }

    public function toRevisi()
    {
        return $this->hasMany(Revisi::class, 'id_kontrak_tahapan', 'id_kontrak_tahapan');
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
