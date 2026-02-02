<?php

namespace App\Models\contract_legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revisi extends Model
{
    use HasFactory;

    protected $connection = 'contract_legal';

    protected $table = 'revisi';
    
    protected $primaryKey = 'id_revisi';

    protected $fillable = [
        'id_kontrak_tahapan',
        'revisi_ke',
        'keterangan',
        'created_by',
        'updated_by'
    ];

    public function toUploadRevisi()
    {
        return $this->hasMany(UploadRevisi::class, 'id_revisi', 'id_revisi');
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
