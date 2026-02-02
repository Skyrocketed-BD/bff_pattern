<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasusL extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'kasus_l';

    protected $primaryKey = 'id_kasus_l';

    protected $fillable = [
        'id_kasus_nl',
        'id_tahapan_l',
        'no',
        'nama',
        'tanggal',
        'keterangan',
        'status',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel kasus_nl
    public function toKasusNl()
    {
        return $this->belongsTo(KasusNl::class, 'id_kasus_nl', 'id_kasus_nl');
    }

    // relasi ke tabel tahapan_l
    public function toTahapanL()
    {
        return $this->belongsTo(TahapanL::class, 'id_tahapan_l', 'id_tahapan_l');
    }

    public function toKasusRiwayatL()
    {
        return $this->hasMany(KasusRiwayatL::class, 'id_kasus_l', 'id_kasus_l');
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
