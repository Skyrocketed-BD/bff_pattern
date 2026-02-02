<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalSidang extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'jadwal_sidang';

    protected $primaryKey = 'id_jadwal_sidang';

    protected $fillable = [
        'id_kasus_riwayat_l',
        'no',
        'nama',
        'tgl_waktu_sidang',
        'keterangan',
        'status',
        'created_by',
        'updated_by',
    ];

    public function toUploadJadwalSidang()
    {
        return $this->hasMany(UploadJadwalSidang::class, 'id_jadwal_sidang', 'id_jadwal_sidang');
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
