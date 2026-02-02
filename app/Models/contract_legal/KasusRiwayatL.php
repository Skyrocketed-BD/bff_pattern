<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasusRiwayatL extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'kasus_riwayat_l';

    protected $primaryKey = 'id_kasus_riwayat_l';

    protected $fillable = [
        'id_kasus_l',
        'id_tahapan_l',
        'nama',
        'tanggal',
        'deskripsi',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel kasus_l
    public function toKasusL()
    {
        return $this->belongsTo(KasusL::class, 'id_kasus_l', 'id_kasus_l');
    }

    // relasi ke tabel tahapan_l
    public function toTahapanL()
    {
        return $this->belongsTo(TahapanL::class, 'id_tahapan_l', 'id_tahapan_l');
    }

    // relasi ke tabel upload_kasus_riwayat_l
    public function toUploadKasusRiwayatL()
    {
        return $this->hasMany(UploadKasusRiwayatL::class, 'id_kasus_riwayat_l', 'id_kasus_riwayat_l');
    }   

    // relasi ke tabel jadwal_sidang
    public function toJadwalSidang()
    {
        return $this->belongsTo(JadwalSidang::class, 'id_kasus_riwayat_l', 'id_kasus_riwayat_l');
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
