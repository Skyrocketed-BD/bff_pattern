<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasusNl extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'kasus_nl';

    protected $primaryKey = 'id_kasus_nl';

    protected $fillable = [
        'id_tahapan_nl',
        'no',
        'nama',
        'tanggal',
        'keterangan',
        'status',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel tahapan_nl
    public function toTahapanNl()
    {
        return $this->belongsTo(TahapanNl::class, 'id_tahapan_nl', 'id_tahapan_nl');
    }

    public function toKasusRiwayatNl()
    {
        return $this->hasMany(KasusRiwayatNl::class, 'id_kasus_nl', 'id_kasus_nl');
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
