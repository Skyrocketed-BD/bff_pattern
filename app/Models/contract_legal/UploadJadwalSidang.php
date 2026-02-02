<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadJadwalSidang extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'upload_jadwal_sidang';

    protected $primaryKey = 'id_upload_jadwal_sidang';

    protected $fillable = [
        'id_jadwal_sidang',
        'judul',
        'file',
        'created_by',
        'updated_by'
    ];

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
