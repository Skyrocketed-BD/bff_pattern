<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasusRiwayatNl extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'kasus_riwayat_nl';

    protected $primaryKey = 'id_kasus_riwayat_nl';

    protected $fillable = [
        'id_kasus_nl',
        'id_tahapan_nl',
        'nama',
        'tanggal',
        'deskripsi',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel kasus_nl
    public function toKasusNl()
    {
        return $this->belongsTo(KasusNl::class, 'id_kasus_nl', 'id_kasus_nl');
    }

    // relasi ke tabel tahapan_nl
    public function toTahapanNl()
    {
        return $this->belongsTo(TahapanNl::class, 'id_tahapan_nl', 'id_tahapan_nl');
    }

    // relasi ke tabel upload_kasus_riwayat_nl
    public function toUploadKasusRiwayatNl()
    {
        return $this->hasMany(UploadKasusRiwayatNl::class, 'id_kasus_riwayat_nl', 'id_kasus_riwayat_nl');
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
