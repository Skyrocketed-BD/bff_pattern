<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadFinal extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'upload_final';

    protected $primaryKey = 'id_upload_final';

    protected $fillable = [
        'id_kontrak',
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
