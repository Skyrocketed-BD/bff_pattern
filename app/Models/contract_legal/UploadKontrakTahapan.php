<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UploadKontrakTahapan extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'upload_kontrak_tahapan';

    protected $primaryKey = 'id_upload_kontrak_tahapan';

    protected $fillable = [
        'id_kontrak_tahapan',
        'judul',
        'file',
        'created_by',
        'updated_by'
    ];

    public function toUploadRevisi()
    {
        return $this->hasMany(UploadRevisi::class, 'id_upload_kontrak_tahapan', 'id_upload_kontrak_tahapan');
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
