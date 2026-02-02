<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class UploadRevisi extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'upload_revisi';

    protected $primaryKey = 'id_upload_revisi';

    protected $fillable = [
        'id_revisi',
        'id_upload_kontrak_tahapan',
        'file',
        'created_by',
        'updated_by'
    ];

    public function originalUpload()
    {
        return $this->belongsTo(UploadKontrakTahapan::class, 'id_upload_kontrak_tahapan');
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
